<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\CompanyAdmin\Concerns\HandlesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    use HandlesCompanyAccess;

    public function index(Request $request): View
    {
        $payments = Payment::with(['client', 'project'])
            ->where('company_id', $this->companyId())
            ->where('payment_type', 'client_project')
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
        $this->abortUnlessCompanyRecord($payment);

        return view('company-admin.payments.show', ['payment' => $payment->load(['client', 'project', 'verifier'])]);
    }

    public function edit(Payment $payment): View
    {
        $this->abortUnlessCompanyRecord($payment);

        return $this->form($payment);
    }

    public function update(Request $request, Payment $payment): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($payment);
        $data = $this->validated($request);
        $this->validateRelated($data);
        $payment->update($data);

        return redirect()->route('company-admin.payments.show', $payment)->with('success', 'Payment updated.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($payment);
        $payment->update(['status' => 'refunded']);

        return redirect()->route('company-admin.payments.index')->with('success', 'Payment marked refunded.');
    }

    public function verify(Request $request, Payment $payment): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($payment);
        $data = $request->validate(['verification_note' => ['nullable', 'string', 'max:2000']]);
        $payment->update(['status' => 'paid', 'verified_by' => auth()->id(), 'verified_at' => now(), 'verification_note' => $data['verification_note'] ?? null, 'paid_at' => now()]);

        return back()->with('success', 'Payment verified.');
    }

    public function reject(Request $request, Payment $payment): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($payment);
        $data = $request->validate(['verification_note' => ['required', 'string', 'max:2000']]);
        $payment->update(['status' => 'rejected', 'verified_by' => auth()->id(), 'verified_at' => now(), 'verification_note' => $data['verification_note']]);

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
            'amount' => ['required', 'numeric', 'min:0'],
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
}
