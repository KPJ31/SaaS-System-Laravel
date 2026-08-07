<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\CompanyAdmin\Concerns\HandlesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Services\AuditLogger;
use App\Services\InvoiceCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaymentController extends Controller
{
    use HandlesCompanyAccess;

    public function index(Request $request): View
    {
        $this->authorizePaymentFilters($request);

        $payments = Payment::with(['client', 'project', 'invoice'])
            ->where('company_id', $this->companyId())
            ->where('payment_type', 'client_project')
            ->when($request->search, fn ($query, $search) => $query->where(function ($query) use ($search): void {
                $query->where('transaction_reference', 'like', "%{$search}%")
                    ->orWhereHas('invoice', fn ($invoice) => $invoice->where('invoice_number', 'like', "%{$search}%"))
                    ->orWhereHas('client', fn ($client) => $client->where('name', 'like', "%{$search}%"));
            }))
            ->when($request->client_id, fn ($query, $clientId) => $query->where('client_id', $clientId))
            ->when($request->project_id, fn ($query, $projectId) => $query->where('project_id', $projectId))
            ->when($request->invoice_id, fn ($query, $invoiceId) => $query->where('invoice_id', $invoiceId))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('company-admin.payments.index', [
            'payments' => $payments,
            'currency' => $this->currency(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Payment([
            'invoice_id' => request()->integer('invoice_id') ?: null,
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $this->validateRelated($data);
        $data = $this->hydrateInvoiceContext($data);
        $data['company_id'] = $this->companyId();
        $data['created_by'] = auth()->id();
        $data['payment_type'] = 'client_project';
        $data['status'] = $data['status'] ?? 'requested';

        $payment = DB::transaction(function () use ($data): Payment {
            $payment = Payment::create($data);
            if ($payment->invoice && in_array($payment->status, InvoiceCalculator::PAID_PAYMENT_STATUSES, true)) {
                app(InvoiceCalculator::class)->syncPaymentState($payment->invoice);
            }

            return $payment;
        });

        return redirect()->route('company-admin.payments.show', $payment)->with('success', 'Payment request created.');
    }

    public function show(Payment $payment): View
    {
        $this->authorizeClientProjectPayment($payment);

        return view('company-admin.payments.show', [
            'payment' => $payment->load(['client', 'project', 'invoice', 'verifier']),
            'currency' => $this->currency(),
        ]);
    }

    public function edit(Payment $payment): View
    {
        $this->authorizeClientProjectPayment($payment);

        return $this->form($payment);
    }

    public function update(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorizeClientProjectPayment($payment);
        $data = $this->validated($request);
        $this->validateRelated($data);
        $data = $this->hydrateInvoiceContext($data);
        DB::transaction(function () use ($payment, $data): void {
            $previousInvoice = $payment->invoice;
            $payment->update($data);
            $calculator = app(InvoiceCalculator::class);
            if ($previousInvoice) {
                $calculator->syncPaymentState($previousInvoice);
            }
            $payment->unsetRelation('invoice');
            if ($payment->invoice) {
                $calculator->syncPaymentState($payment->invoice);
            }
        });

        return redirect()->route('company-admin.payments.show', $payment)->with('success', 'Payment updated.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $this->authorizeClientProjectPayment($payment);
        if (! $this->canTransition($payment->status, 'refunded')) {
            return back()->with('error', 'Only paid payments can be marked as refunded.');
        }

        DB::transaction(function () use ($payment): void {
            $payment->update(['status' => 'refunded']);
            if ($payment->invoice) {
                app(InvoiceCalculator::class)->syncPaymentState($payment->invoice);
            }
        });

        return redirect()->route('company-admin.payments.index')->with('success', 'Payment marked refunded.');
    }

    public function verify(Request $request, Payment $payment, AuditLogger $logger, InvoiceCalculator $calculator): RedirectResponse
    {
        $this->authorizeClientProjectPayment($payment);
        $data = $request->validate(['verification_note' => ['nullable', 'string', 'max:2000']]);

        if (! $this->canTransition($payment->status, 'paid')) {
            return back()->with('error', 'Only submitted payment proofs can be verified.');
        }

        DB::transaction(function () use ($request, $payment, $data, $logger, $calculator): void {
            $old = $payment->only(['status', 'verified_by', 'verified_at', 'verification_note', 'paid_at']);
            $payment->update(['status' => 'paid', 'verified_by' => auth()->id(), 'verified_at' => now(), 'verification_note' => $data['verification_note'] ?? null, 'paid_at' => now()]);
            if ($payment->invoice) {
                $calculator->syncPaymentState($payment->invoice);
            }
            $logger->record('payment_verified', 'Client-project payment verified.', auth()->user(), $payment, $this->companyId(), ['old' => $old, 'new' => $payment->fresh()->only(['status', 'verified_by', 'verified_at', 'verification_note', 'paid_at'])], $request);
        });

        return back()->with('success', 'Payment verified.');
    }

    public function reject(Request $request, Payment $payment, AuditLogger $logger): RedirectResponse
    {
        $this->authorizeClientProjectPayment($payment);
        $data = $request->validate(['verification_note' => ['required', 'string', 'max:2000']]);

        if (! $this->canTransition($payment->status, 'rejected')) {
            return back()->with('error', 'Only submitted payment proofs can be rejected.');
        }

        DB::transaction(function () use ($request, $payment, $data, $logger): void {
            $old = $payment->only(['status', 'verified_by', 'verified_at', 'verification_note']);
            $payment->update(['status' => 'rejected', 'verified_by' => auth()->id(), 'verified_at' => now(), 'verification_note' => $data['verification_note']]);
            $logger->record('payment_rejected', 'Client-project payment rejected.', auth()->user(), $payment, $this->companyId(), ['old' => $old, 'reason' => $data['verification_note']], $request);
        });

        return back()->with('success', 'Payment rejected.');
    }

    private function form(Payment $payment): View
    {
        return view('company-admin.payments.form', [
            'payment' => $payment,
            'clients' => Client::where('company_id', $this->companyId())->orderBy('name')->get(),
            'projects' => Project::where('company_id', $this->companyId())->orderBy('name')->get(),
            'invoices' => Invoice::where('company_id', $this->companyId())
                ->whereNot('status', 'cancelled')
                ->orderByDesc('issue_date')
                ->get(),
            'currency' => $this->currency(),
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'transaction_reference' => ['nullable', 'string', 'max:120'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'string', 'max:80'],
            'status' => ['required', 'in:pending,requested,proof_submitted,partially_paid,paid,rejected,refunded,received'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);
    }

    private function validateRelated(array $data): void
    {
        $invoice = null;

        if (! empty($data['invoice_id'])) {
            $invoice = Invoice::where('company_id', $this->companyId())->whereKey($data['invoice_id'])->firstOrFail();
        }

        if (! empty($data['client_id'])) {
            abort_unless(Client::where('company_id', $this->companyId())->whereKey($data['client_id'])->exists(), 403);
        }

        if (! empty($data['project_id'])) {
            $project = Project::where('company_id', $this->companyId())->whereKey($data['project_id'])->firstOrFail();
            if (! empty($data['client_id'])) {
                abort_unless((int) $project->client_id === (int) $data['client_id'], 422, 'Selected project does not belong to the selected client.');
            }
        }

        if ($invoice) {
            if (! empty($data['client_id'])) {
                abort_unless((int) $invoice->client_id === (int) $data['client_id'], 422, 'Selected invoice does not belong to the selected client.');
            }

            if (! empty($data['project_id'])) {
                abort_unless((int) $invoice->project_id === (int) $data['project_id'], 422, 'Selected invoice does not belong to the selected project.');
            }
        }
    }

    private function hydrateInvoiceContext(array $data): array
    {
        if (empty($data['invoice_id'])) {
            return $data;
        }

        $invoice = Invoice::where('company_id', $this->companyId())->findOrFail($data['invoice_id']);
        $data['client_id'] = $invoice->client_id;
        $data['project_id'] = $invoice->project_id;

        return $data;
    }

    private function authorizePaymentFilters(Request $request): void
    {
        if ($request->filled('client_id')) {
            abort_unless(Client::where('company_id', $this->companyId())->whereKey($request->integer('client_id'))->exists(), 403);
        }

        if ($request->filled('project_id')) {
            abort_unless(Project::where('company_id', $this->companyId())->whereKey($request->integer('project_id'))->exists(), 403);
        }

        if ($request->filled('invoice_id')) {
            abort_unless(Invoice::where('company_id', $this->companyId())->whereKey($request->integer('invoice_id'))->exists(), 403);
        }
    }

    private function authorizeClientProjectPayment(Payment $payment): void
    {
        $this->abortUnlessCompanyRecord($payment);
        abort_unless($payment->payment_type === 'client_project', 404);
    }

    private function canTransition(string $currentStatus, string $newStatus): bool
    {
        if ($currentStatus === $newStatus) {
            return false;
        }

        return match ($currentStatus) {
            'pending', 'requested', 'proof_submitted', 'partially_paid' => in_array($newStatus, ['paid', 'rejected'], true),
            'paid', 'received' => $newStatus === 'refunded',
            default => false,
        };
    }

    private function currency(): string
    {
        return auth()->user()->company?->setting?->currency ?? 'USD';
    }
}
