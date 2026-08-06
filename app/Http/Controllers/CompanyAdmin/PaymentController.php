<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\CompanyAdmin\Concerns\HandlesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Project;
use App\Services\AuditLogger;
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

        $payments = Payment::with(['client', 'project'])
            ->where('company_id', $this->companyId())
            ->where('payment_type', 'client_project')
            ->when($request->search, fn ($query, $search) => $query->where('transaction_reference', 'like', "%{$search}%"))
            ->when($request->client_id, fn ($query, $clientId) => $query->where('client_id', $clientId))
            ->when($request->project_id, fn ($query, $projectId) => $query->where('project_id', $projectId))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('company-admin.payments.index', compact('payments'));
    }

    public function create(): View
    {
        return $this->form(new Payment());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $this->validateRelated($data);
        $data['company_id'] = $this->companyId();
        $data['created_by'] = auth()->id();
        $data['payment_type'] = 'client_project';
        $data['status'] = $data['status'] ?? 'requested';

        $payment = Payment::create($data);

        return redirect()->route('company-admin.payments.show', $payment)->with('success', 'Payment request created.');
    }

    public function show(Payment $payment): View
    {
        $this->authorizeClientProjectPayment($payment);

        return view('company-admin.payments.show', ['payment' => $payment->load(['client', 'project', 'verifier'])]);
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
        $payment->update($data);

        return redirect()->route('company-admin.payments.show', $payment)->with('success', 'Payment updated.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $this->authorizeClientProjectPayment($payment);
        if (! $this->canTransition($payment->status, 'refunded')) {
            return back()->with('error', 'Only paid payments can be marked as refunded.');
        }

        $payment->update(['status' => 'refunded']);

        return redirect()->route('company-admin.payments.index')->with('success', 'Payment marked refunded.');
    }

    public function verify(Request $request, Payment $payment, AuditLogger $logger): RedirectResponse
    {
        $this->authorizeClientProjectPayment($payment);
        $data = $request->validate(['verification_note' => ['nullable', 'string', 'max:2000']]);

        if (! $this->canTransition($payment->status, 'paid')) {
            return back()->with('error', 'Only submitted payment proofs can be verified.');
        }

        DB::transaction(function () use ($request, $payment, $data, $logger): void {
            $old = $payment->only(['status', 'verified_by', 'verified_at', 'verification_note', 'paid_at']);
            $payment->update(['status' => 'paid', 'verified_by' => auth()->id(), 'verified_at' => now(), 'verification_note' => $data['verification_note'] ?? null, 'paid_at' => now()]);
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
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
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
        if (! empty($data['client_id'])) {
            abort_unless(Client::where('company_id', $this->companyId())->whereKey($data['client_id'])->exists(), 403);
        }

        if (! empty($data['project_id'])) {
            abort_unless(Project::where('company_id', $this->companyId())->whereKey($data['project_id'])->exists(), 403);
        }
    }

    private function authorizePaymentFilters(Request $request): void
    {
        if ($request->filled('client_id')) {
            abort_unless(Client::where('company_id', $this->companyId())->whereKey($request->integer('client_id'))->exists(), 403);
        }

        if ($request->filled('project_id')) {
            abort_unless(Project::where('company_id', $this->companyId())->whereKey($request->integer('project_id'))->exists(), 403);
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
}
