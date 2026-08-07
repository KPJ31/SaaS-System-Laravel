<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\CompanyRegistrationRequest;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Subscription;
use App\Models\SubscriptionChangeRequest;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SuperAdminDashboardService
{
    private const RECOGNIZED_PAYMENT_STATUSES = ['verified', 'received', 'paid'];
    private const PENDING_PAYMENT_STATUSES = ['pending', 'submitted', 'proof_submitted', 'requested'];

    public function payload(): array
    {
        $months = collect(range(5, 0))->map(fn (int $offset) => now()->startOfMonth()->subMonths($offset));
        $recognizedPayments = Payment::query()
            ->where('payment_type', 'subscription')
            ->whereIn('status', self::RECOGNIZED_PAYMENT_STATUSES);
        $monthStart = now()->startOfMonth();

        $pendingRequests = CompanyRegistrationRequest::where('status', 'pending')->count();
        $pendingPayments = Payment::where('payment_type', 'subscription')->whereIn('status', self::PENDING_PAYMENT_STATUSES)->count();
        $pendingPlanChanges = SubscriptionChangeRequest::whereIn('status', ['pending', 'payment_submitted', 'under_review'])->count();
        $expiringSubscriptions = Subscription::whereIn('status', ['trialing', 'active'])
            ->whereDate('renews_at', '>=', today())
            ->whereDate('renews_at', '<=', today()->addDays(30))
            ->count();

        return [
            'currency' => (string) SystemSetting::getValue('currency', 'USD'),
            'primaryStats' => [
                ['label' => 'Total Companies', 'value' => Company::count(), 'icon' => 'fa-building', 'type' => 'primary'],
                ['label' => 'Active Companies', 'value' => Company::where('status', 'active')->count(), 'icon' => 'fa-building-circle-check', 'type' => 'green'],
                ['label' => 'Pending Requests', 'value' => $pendingRequests, 'icon' => 'fa-building-circle-exclamation', 'type' => 'yellow'],
                ['label' => 'Total Users', 'value' => User::count(), 'icon' => 'fa-users', 'type' => 'blue'],
                ['label' => 'Total Projects', 'value' => Project::count(), 'icon' => 'fa-diagram-project', 'type' => 'primary'],
                ['label' => 'Active Subscriptions', 'value' => Subscription::whereIn('status', ['trialing', 'active'])->count(), 'icon' => 'fa-repeat', 'type' => 'blue'],
                ['label' => 'Pending Payments', 'value' => $pendingPayments, 'icon' => 'fa-credit-card', 'type' => 'yellow'],
                ['label' => 'Revenue This Month', 'value' => $this->money((float) (clone $recognizedPayments)->whereDate('created_at', '>=', $monthStart)->sum('amount')), 'icon' => 'fa-chart-line', 'type' => 'green'],
            ],
            'attentionItems' => $this->attentionItems($pendingRequests, $pendingPayments, $pendingPlanChanges, $expiringSubscriptions),
            'recentCompanies' => Company::with([
                'activeSubscription.plan',
                'users' => fn ($query) => $query->where('role', 'company_admin')->select('id', 'company_id', 'name', 'email'),
            ])
                ->withCount(['users', 'projects'])
                ->latest()
                ->take(6)
                ->get(),
            'latestAuditLogs' => AuditLog::with(['user', 'company'])->latest()->take(10)->get(),
            'chartLabels' => $months->map(fn (Carbon $month) => $month->format('M Y'))->values(),
            'companyGrowth' => $this->monthlyCounts(Company::query(), $months),
            'userGrowth' => $this->monthlyCounts(User::query(), $months),
            'revenueGrowth' => $this->monthlyRevenue($months),
            'companyStatusLabels' => Company::selectRaw('status, count(*) as total')->groupBy('status')->pluck('status')->values(),
            'companyStatusValues' => Company::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total')->values(),
            'planUsageLabels' => SubscriptionPlan::withCount('subscriptions')->orderBy('display_order')->pluck('name'),
            'planUsageValues' => SubscriptionPlan::withCount('subscriptions')->orderBy('display_order')->pluck('subscriptions_count'),
        ];
    }

    private function attentionItems(int $pendingRequests, int $pendingPayments, int $pendingPlanChanges, int $expiringSubscriptions): array
    {
        return [
            [
                'label' => 'Company requests awaiting review',
                'count' => $pendingRequests,
                'icon' => 'fa-building-circle-check',
                'url' => route('super-admin.company-requests.index', ['status' => 'pending']),
                'tone' => 'warning',
            ],
            [
                'label' => 'Subscription payments to verify',
                'count' => $pendingPayments,
                'icon' => 'fa-credit-card',
                'url' => route('super-admin.payments.index', ['status' => 'pending']),
                'tone' => 'warning',
            ],
            [
                'label' => 'Plan changes in review',
                'count' => $pendingPlanChanges,
                'icon' => 'fa-code-compare',
                'url' => route('super-admin.subscription-change-requests.index', ['status' => 'under_review']),
                'tone' => 'info',
            ],
            [
                'label' => 'Subscriptions renewing within 30 days',
                'count' => $expiringSubscriptions,
                'icon' => 'fa-calendar-days',
                'url' => route('super-admin.reports.show', 'subscription-expiry'),
                'tone' => 'neutral',
            ],
        ];
    }

    private function monthlyCounts($query, Collection $months): Collection
    {
        $records = $query
            ->whereDate('created_at', '>=', $months->first()->toDateString())
            ->get(['created_at'])
            ->groupBy(fn ($record) => $record->created_at->format('Y-m'));

        return $months->map(fn (Carbon $month) => $records->get($month->format('Y-m'), collect())->count())->values();
    }

    private function monthlyRevenue(Collection $months): Collection
    {
        $payments = Payment::query()
            ->where('payment_type', 'subscription')
            ->whereIn('status', self::RECOGNIZED_PAYMENT_STATUSES)
            ->whereDate('created_at', '>=', $months->first()->toDateString())
            ->get(['amount', 'verified_at', 'paid_at', 'created_at'])
            ->groupBy(fn (Payment $payment) => ($payment->verified_at ?? $payment->paid_at ?? $payment->created_at)->format('Y-m'));

        return $months->map(fn (Carbon $month) => (float) $payments->get($month->format('Y-m'), collect())->sum('amount'))->values();
    }

    private function money(float $amount): string
    {
        return (string) SystemSetting::getValue('currency', 'USD').' '.number_format($amount, 2);
    }
}
