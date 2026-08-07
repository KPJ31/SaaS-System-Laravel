<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $payments = Payment::with(['company', 'subscriptionPlan', 'verifier'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $query->where(function ($searchQuery) use ($request): void {
                    $searchQuery->where('transaction_reference', 'like', '%'.$request->search.'%')
                        ->orWhereHas('company', fn ($company) => $company->where('name', 'like', '%'.$request->search.'%'));
                });
            })
            ->when($request->filled('company'), fn ($query) => $query->where('company_id', $request->company))
            ->when($request->filled('plan'), fn ($query) => $query->where('subscription_plan_id', $request->plan))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->where(function ($query): void {
                $query->where('payment_type', 'subscription')->orWhereNull('payment_type');
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $verified = Payment::where('payment_type', 'subscription')->whereIn('status', ['verified', 'received', 'paid']);

        return view('super-admin.payments.index', [
            'payments' => $payments,
            'companies' => Company::orderBy('name')->get(),
            'plans' => SubscriptionPlan::orderBy('name')->get(),
            'statuses' => ['pending', 'submitted', 'verified', 'rejected', 'failed', 'refunded', 'received', 'paid'],
            'totalRevenue' => (clone $verified)->sum('amount'),
            'monthlyRevenue' => (clone $verified)->whereMonth(DB::raw('COALESCE(paid_at, created_at)'), now()->month)->sum('amount'),
            'pendingRevenue' => Payment::where('payment_type', 'subscription')->whereIn('status', ['pending', 'submitted'])->sum('amount'),
            'currency' => (string) SystemSetting::getValue('currency', 'USD'),
        ]);
    }

    public function show(Payment $payment): View
    {
        $this->abortIfNotSubscriptionPayment($payment);

        return view('super-admin.payments.show', [
            'payment' => $payment->load(['company', 'subscription', 'subscriptionPlan', 'project', 'client', 'verifier']),
            'currency' => (string) SystemSetting::getValue('currency', 'USD'),
        ]);
    }

    public function updateStatus(Request $request, Payment $payment, string $status): RedirectResponse
    {
        $this->abortIfNotSubscriptionPayment($payment);

        abort_unless(in_array($status, ['verified', 'rejected', 'failed', 'refunded'], true), 404);

        if (! $this->canTransition($payment->status, $status)) {
            return back()->with('error', 'This payment cannot be changed from '.$payment->status.' to '.$status.'.');
        }

        $data = $request->validate(['verification_note' => ['nullable', 'string', 'max:1000']]);

        DB::transaction(function () use ($request, $payment, $status, $data): void {
            $old = $payment->only(['status', 'verified_by', 'verified_at', 'verification_note']);
            $payment->update([
                'status' => $status,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'verification_note' => $data['verification_note'] ?? null,
            ]);

            $changeRequest = $payment->subscriptionChangeRequest;

            if ($status === 'verified' && $changeRequest) {
                $changeRequest->update(['status' => 'under_review']);
            } elseif ($status === 'verified' && $payment->payment_type === 'subscription' && $payment->subscription) {
                $renewFrom = $payment->subscription->renews_at && $payment->subscription->renews_at->isFuture()
                    ? $payment->subscription->renews_at
                    : now();

                $payment->subscription->update([
                    'status' => 'active',
                    'subscription_plan_id' => $payment->subscription_plan_id ?: $payment->subscription->subscription_plan_id,
                    'monthly_price' => $payment->amount,
                    'renews_at' => $renewFrom->copy()->addMonth()->toDateString(),
                    'ends_at' => null,
                ]);
            }

            AuditLog::create([
                'company_id' => $payment->company_id,
                'user_id' => auth()->id(),
                'action' => 'payment_'.$status,
                'module' => 'Payments and Revenue',
                'auditable_type' => Payment::class,
                'auditable_id' => $payment->id,
                'description' => ucfirst($status).' payment '.($payment->transaction_reference ?: '#'.$payment->id),
                'old_values' => $old,
                'new_values' => $payment->only(['status', 'verified_by', 'verified_at', 'verification_note']),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return back()->with('success', 'Payment status updated.');
    }

    private function canTransition(string $currentStatus, string $newStatus): bool
    {
        if ($currentStatus === $newStatus) {
            return false;
        }

        return match ($currentStatus) {
            'pending', 'submitted', 'proof_submitted' => in_array($newStatus, ['verified', 'rejected', 'failed'], true),
            'verified', 'received', 'paid' => $newStatus === 'refunded',
            default => false,
        };
    }

    private function abortIfNotSubscriptionPayment(Payment $payment): void
    {
        abort_unless($payment->payment_type === 'subscription' || $payment->payment_type === null, 404);
    }
}
