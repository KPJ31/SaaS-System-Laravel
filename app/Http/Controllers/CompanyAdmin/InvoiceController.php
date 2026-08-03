<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\CompanyAdmin\Concerns\HandlesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            ->when($request->search, fn ($query, $search) => $query->where('invoice_number', 'like', "%{$search}%"))
            ->when($request->client_id, fn ($query, $clientId) => $query->where('client_id', $clientId))
            ->when($request->project_id, fn ($query, $projectId) => $query->where('project_id', $projectId))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('company-admin.invoices.index', compact('invoices'));
    }

    public function create(): View
    {
        return $this->form(new Invoice(['issue_date' => now(), 'status' => 'draft']));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $this->validateRelated($data);
        $data['company_id'] = $this->companyId();
        $data['invoice_number'] = $data['invoice_number'] ?? $this->nextInvoiceNumber();
        $data['balance_amount'] = max(0, (float) $data['total'] - (float) ($data['paid_amount'] ?? 0));

        $invoice = DB::transaction(fn () => Invoice::create($data));

        return redirect()->route('company-admin.invoices.show', $invoice)->with('success', 'Invoice created successfully.');
    }

    public function show(Invoice $invoice): View
    {
        $this->abortUnlessCompanyRecord($invoice);

        return view('company-admin.invoices.show', ['invoice' => $invoice->load(['client', 'project', 'items'])]);
    }

    public function edit(Invoice $invoice): View
    {
        $this->abortUnlessCompanyRecord($invoice);
        abort_unless($invoice->status === 'draft', 422, 'Only draft invoices can be edited.');

        return $this->form($invoice);
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($invoice);
        abort_unless($invoice->status === 'draft', 422, 'Only draft invoices can be edited.');
        $data = $this->validated($request);
        $this->validateRelated($data);
        $data['balance_amount'] = max(0, (float) $data['total'] - (float) ($data['paid_amount'] ?? 0));
        DB::transaction(fn () => $invoice->update($data));

        return redirect()->route('company-admin.invoices.show', $invoice)->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($invoice);
        if (! $this->canTransition($invoice->status, 'cancelled')) {
            return back()->with('error', 'This invoice cannot be cancelled from its current status.');
        }

        $invoice->update(['status' => 'cancelled']);

        return redirect()->route('company-admin.invoices.index')->with('success', 'Invoice cancelled.');
    }

    public function print(Invoice $invoice): View
    {
        $this->abortUnlessCompanyRecord($invoice);

        return view('company-admin.invoices.print', ['invoice' => $invoice->load(['company', 'client', 'project', 'items'])]);
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

    public function markPaid(Invoice $invoice, AuditLogger $logger): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($invoice);

        if (! $this->canTransition($invoice->status, 'paid')) {
            return back()->with('error', 'This invoice cannot be marked paid from its current status.');
        }

        DB::transaction(function () use ($invoice, $logger): void {
            $old = $invoice->only(['status', 'paid_amount', 'balance_amount']);
            $invoice->update(['status' => 'paid', 'paid_amount' => $invoice->total, 'balance_amount' => 0]);
            $logger->record('invoice_paid', 'Invoice marked as paid.', auth()->user(), $invoice, $this->companyId(), ['old' => $old], request());
        });

        return back()->with('success', 'Invoice marked paid.');
    }

    private function form(Invoice $invoice): View
    {
        return view('company-admin.invoices.form', [
            'invoice' => $invoice,
            'clients' => Client::where('company_id', $this->companyId())->orderBy('name')->get(),
            'projects' => Project::where('company_id', $this->companyId())->orderBy('name')->get(),
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
            'subtotal' => ['required', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0', 'lte:total'],
            'status' => ['required', 'in:draft,sent,partially_paid,paid,overdue,cancelled'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);
    }

    private function validateRelated(array $data): void
    {
        abort_unless(Client::where('company_id', $this->companyId())->whereKey($data['client_id'])->exists(), 403);

        if (! empty($data['project_id'])) {
            abort_unless(Project::where('company_id', $this->companyId())->whereKey($data['project_id'])->exists(), 403);
        }
    }

    private function nextInvoiceNumber(): string
    {
        $count = Invoice::where('company_id', $this->companyId())->whereYear('created_at', now()->year)->count() + 1;

        return 'INV-'.now()->year.'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
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
}
