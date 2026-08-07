<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\CompanyAdmin\Concerns\HandlesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Models\Project;
use App\Services\AuditLogger;
use App\Services\InvoiceCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    use HandlesCompanyAccess;

    public function index(Request $request): View
    {
        $this->authorizeInvoiceFilters($request);

        $invoices = Invoice::with(['client', 'project'])
            ->where('company_id', $this->companyId())
            ->when($request->search, fn ($query, $search) => $query->where(function ($query) use ($search): void {
                $query->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($client) => $client->where('name', 'like', "%{$search}%"));
            }))
            ->when($request->client_id, fn ($query, $clientId) => $query->where('client_id', $clientId))
            ->when($request->project_id, fn ($query, $projectId) => $query->where('project_id', $projectId))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('company-admin.invoices.index', [
            'invoices' => $invoices,
            'currency' => $this->currency(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Invoice(['issue_date' => now(), 'status' => 'draft']));
    }

    public function store(Request $request, InvoiceCalculator $calculator, AuditLogger $logger): RedirectResponse
    {
        $data = $this->validated($request);
        $this->validateRelated($data);
        [$invoiceData, $items] = $this->invoicePayload($data, $calculator);

        $invoice = DB::transaction(function () use ($invoiceData, $items, $logger): Invoice {
            $invoice = Invoice::create($invoiceData);
            if ($items !== []) {
                $invoice->items()->createMany($items);
            }
            $logger->record('invoice_created', 'Invoice created.', auth()->user(), $invoice, $this->companyId(), ['total' => $invoice->total], request());

            return $invoice;
        });

        return redirect()->route('company-admin.invoices.show', $invoice)->with('success', 'Invoice created successfully.');
    }

    public function show(Invoice $invoice): View
    {
        $this->abortUnlessCompanyRecord($invoice);

        return view('company-admin.invoices.show', [
            'invoice' => $invoice->load(['client', 'project', 'items', 'payments.verifier']),
            'currency' => $this->currency(),
        ]);
    }

    public function edit(Invoice $invoice): View
    {
        $this->abortUnlessCompanyRecord($invoice);
        abort_unless($invoice->status === 'draft', 422, 'Only draft invoices can be edited.');

        return $this->form($invoice);
    }

    public function update(Request $request, Invoice $invoice, InvoiceCalculator $calculator, AuditLogger $logger): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($invoice);
        abort_unless($invoice->status === 'draft', 422, 'Only draft invoices can be edited.');
        $data = $this->validated($request);
        $this->validateRelated($data);
        [$invoiceData, $items] = $this->invoicePayload($data, $calculator, $invoice);
        DB::transaction(function () use ($invoice, $invoiceData, $items, $logger): void {
            $old = $invoice->only(['client_id', 'project_id', 'subtotal', 'tax', 'total', 'status']);
            $invoice->update($invoiceData);
            $invoice->items()->delete();
            if ($items !== []) {
                $invoice->items()->createMany($items);
            }
            $logger->record('invoice_updated', 'Invoice updated.', auth()->user(), $invoice, $this->companyId(), ['old' => $old, 'new' => $invoice->fresh()->only(['client_id', 'project_id', 'subtotal', 'tax', 'total', 'status'])], request());
        });

        return redirect()->route('company-admin.invoices.show', $invoice)->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($invoice);
        if (! $this->canTransition($invoice->status, 'cancelled')) {
            return back()->with('error', 'This invoice cannot be cancelled from its current status.');
        }

        $old = $invoice->only(['status']);
        $invoice->update(['status' => 'cancelled']);
        app(AuditLogger::class)->record('invoice_cancelled', 'Invoice cancelled.', auth()->user(), $invoice, $this->companyId(), ['old' => $old], request());

        return redirect()->route('company-admin.invoices.index')->with('success', 'Invoice cancelled.');
    }

    public function print(Invoice $invoice): View
    {
        $this->abortUnlessCompanyRecord($invoice);

        return view('company-admin.invoices.print', [
            'invoice' => $invoice->load(['company', 'client', 'project', 'items']),
            'currency' => $this->currency(),
            'paymentInstructions' => $this->companySetting()?->settings['payment_instructions'] ?? null,
        ]);
    }

    public function send(Invoice $invoice, AuditLogger $logger): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($invoice);

        if (! $this->canTransition($invoice->status, 'sent')) {
            return back()->with('error', 'Only draft invoices can be sent.');
        }

        try {
            $invoice->update(['status' => 'sent']);
            $logger->record('invoice_sent', 'Invoice marked as sent.', auth()->user(), $invoice, $this->companyId(), request: request());
        } catch (\Throwable $exception) {
            Log::warning('Invoice send workflow failed', ['invoice_id' => $invoice->id, 'error' => $exception->getMessage()]);
        }

        return back()->with('success', 'Invoice marked as sent.');
    }

    public function markPaid(Invoice $invoice, AuditLogger $logger, InvoiceCalculator $calculator): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($invoice);

        if (! $this->canTransition($invoice->status, 'paid')) {
            return back()->with('error', 'This invoice cannot be marked paid from its current status.');
        }

        DB::transaction(function () use ($invoice, $logger): void {
            $old = $invoice->only(['status', 'paid_amount', 'balance_amount']);
            $invoice->payments()->create([
                'company_id' => $this->companyId(),
                'client_id' => $invoice->client_id,
                'project_id' => $invoice->project_id,
                'created_by' => auth()->id(),
                'transaction_reference' => 'INV-'.$invoice->invoice_number.'-MANUAL',
                'payment_type' => 'client_project',
                'amount' => max(0, (float) $invoice->balance_amount),
                'method' => 'manual',
                'status' => 'paid',
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'paid_at' => now()->toDateString(),
                'notes' => 'Created when invoice was manually marked paid.',
            ]);
            app(InvoiceCalculator::class)->syncPaymentState($invoice);
            $logger->record('invoice_paid', 'Invoice marked as paid.', auth()->user(), $invoice, $this->companyId(), ['old' => $old, 'new' => $invoice->fresh()->only(['status', 'paid_amount', 'balance_amount'])], request());
        });

        return back()->with('success', 'Invoice marked paid.');
    }

    private function form(Invoice $invoice): View
    {
        return view('company-admin.invoices.form', [
            'invoice' => $invoice,
            'clients' => Client::where('company_id', $this->companyId())->orderBy('name')->get(),
            'projects' => Project::where('company_id', $this->companyId())->orderBy('name')->get(),
            'currency' => $this->currency(),
            'defaultTaxPercentage' => (float) ($this->companySetting()?->settings['default_tax_percentage'] ?? 0),
        ]);
    }

    private function validated(Request $request): array
    {
        $invoiceId = $request->route('invoice')?->id;

        return $request->validate([
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'invoice_number' => ['nullable', 'string', 'max:80', Rule::unique('invoices', 'invoice_number')->where('company_id', $this->companyId())->ignore($invoiceId)],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'subtotal' => ['nullable', 'required_without:items', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'total' => ['nullable', 'required_without:items', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:draft,sent,partially_paid,paid,overdue,cancelled'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'items' => ['nullable', 'array'],
            'items.*.description' => ['required_with:items', 'string', 'max:255'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'min:0.01', 'max:999999.99'],
            'items.*.unit_price' => ['required_with:items', 'numeric', 'min:0', 'max:999999999.99'],
        ]);
    }

    private function validateRelated(array $data): void
    {
        abort_unless(Client::where('company_id', $this->companyId())->whereKey($data['client_id'])->exists(), 403);

        if (! empty($data['project_id'])) {
            $project = Project::where('company_id', $this->companyId())->whereKey($data['project_id'])->firstOrFail();
            abort_unless((int) $project->client_id === (int) $data['client_id'], 422, 'Selected project does not belong to the selected client.');
        }
    }

    private function nextInvoiceNumber(): string
    {
        $count = Invoice::where('company_id', $this->companyId())->whereYear('created_at', now()->year)->count() + 1;
        $prefix = $this->companySetting()?->settings['invoice_prefix'] ?? 'INV';

        return $prefix.'-'.now()->year.'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    private function authorizeInvoiceFilters(Request $request): void
    {
        if ($request->filled('client_id')) {
            abort_unless(Client::where('company_id', $this->companyId())->whereKey($request->integer('client_id'))->exists(), 403);
        }

        if ($request->filled('project_id')) {
            abort_unless(Project::where('company_id', $this->companyId())->whereKey($request->integer('project_id'))->exists(), 403);
        }
    }

    private function canTransition(string $currentStatus, string $newStatus): bool
    {
        if ($currentStatus === $newStatus) {
            return false;
        }

        return match ($currentStatus) {
            'draft' => in_array($newStatus, ['sent', 'cancelled'], true),
            'sent', 'partially_paid', 'overdue' => in_array($newStatus, ['paid', 'cancelled'], true),
            default => false,
        };
    }

    private function invoicePayload(array $data, InvoiceCalculator $calculator, ?Invoice $invoice = null): array
    {
        $items = $data['items'] ?? [];

        if ($items !== []) {
            $totals = $calculator->totalsFromItems($items, (float) ($data['tax'] ?? 0));
            if ($totals['items'] === []) {
                throw ValidationException::withMessages(['items' => 'Add at least one invoice item.']);
            }
        } else {
            $subtotal = round((float) ($data['subtotal'] ?? 0), 2);
            $tax = round((float) ($data['tax'] ?? 0), 2);
            $total = round((float) ($data['total'] ?? ($subtotal + $tax)), 2);
            $totals = ['subtotal' => $subtotal, 'tax' => $tax, 'total' => $total, 'items' => []];
        }

        $paidAmount = round((float) ($data['paid_amount'] ?? 0), 2);
        if ($paidAmount > $totals['total']) {
            throw ValidationException::withMessages(['paid_amount' => 'The paid amount must not be greater than the invoice total.']);
        }

        $status = $data['status'];
        if ($paidAmount > 0 && $paidAmount < $totals['total'] && ! in_array($status, ['draft', 'cancelled'], true)) {
            $status = 'partially_paid';
        }
        if ($paidAmount >= $totals['total'] && $totals['total'] > 0 && ! in_array($status, ['draft', 'cancelled'], true)) {
            $status = 'paid';
        }

        return [[
            'company_id' => $this->companyId(),
            'client_id' => $data['client_id'],
            'project_id' => $data['project_id'] ?? null,
            'invoice_number' => $data['invoice_number'] ?? $invoice?->invoice_number ?? $this->nextInvoiceNumber(),
            'issue_date' => $data['issue_date'],
            'due_date' => $data['due_date'] ?? null,
            'subtotal' => $totals['subtotal'],
            'tax' => $totals['tax'],
            'total' => $totals['total'],
            'paid_amount' => $paidAmount,
            'balance_amount' => round(max(0, $totals['total'] - $paidAmount), 2),
            'status' => $status,
            'notes' => $data['notes'] ?? null,
        ], $totals['items']];
    }

    private function companySetting(): ?CompanySetting
    {
        return CompanySetting::where('company_id', $this->companyId())->first();
    }

    private function currency(): string
    {
        return $this->companySetting()?->currency ?? 'USD';
    }
}
