<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\CompanyRegistrationRequest;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $companyGrowth = Company::query()
            ->whereYear('created_at', now()->year)
            ->get(['created_at'])
            ->groupBy(fn (Company $company) => (int) $company->created_at->format('n'))
            ->map->count();

        $revenueGrowth = Payment::query()
            ->where('payment_type', 'subscription')
            ->whereIn('status', ['verified', 'received', 'paid'])
            ->get(['amount', 'paid_at', 'created_at'])
            ->filter(fn (Payment $payment) => ($payment->paid_at ?? $payment->created_at)->year === now()->year)
            ->groupBy(fn (Payment $payment) => (int) ($payment->paid_at ?? $payment->created_at)->format('n'))
            ->map(fn ($payments) => $payments->sum('amount'));

        $months = collect(range(1, 12))->map(fn (int $month) => now()->month($month)->format('M'));

        return view('super-admin.dashboard', [
            'companiesCount' => Company::count(),
            'activeCompaniesCount' => Company::where('status', 'active')->count(),
            'pendingCompaniesCount' => Company::where('status', 'pending')->count(),
            'suspendedCompaniesCount' => Company::where('status', 'suspended')->count(),
            'rejectedCompaniesCount' => Company::where('status', 'rejected')->count(),
            'pendingRequestsCount' => CompanyRegistrationRequest::where('status', 'pending')->count(),
            'companyAdminsCount' => User::where('role', 'company_admin')->count(),
            'subscriptionPlansCount' => SubscriptionPlan::count(),
            'activeSubscriptionsCount' => Subscription::whereIn('status', ['trialing', 'active'])->count(),
            'expiredSubscriptionsCount' => Subscription::where('status', 'expired')->count(),
            'monthlyRevenue' => Subscription::where('status', 'active')->sum('monthly_price'),
            'totalRevenue' => Payment::where('payment_type', 'subscription')->whereIn('status', ['verified', 'received', 'paid'])->sum('amount'),
            'recentCompanies' => Company::latest()->take(5)->get(),
            'latestRequests' => CompanyRegistrationRequest::latest()->take(5)->get(),
            'recentPayments' => Payment::with(['company', 'subscriptionPlan'])->where('payment_type', 'subscription')->latest()->take(5)->get(),
            'latestAuditLogs' => AuditLog::with('user')->latest()->take(6)->get(),
            'chartLabels' => $months,
            'companyGrowth' => collect(range(1, 12))->map(fn (int $month) => (int) ($companyGrowth[$month] ?? 0)),
            'revenueGrowth' => collect(range(1, 12))->map(fn (int $month) => (float) ($revenueGrowth[$month] ?? 0)),
            'companyStatusLabels' => Company::select('status')->distinct()->pluck('status'),
            'companyStatusValues' => Company::query()->get(['status'])->groupBy('status')->map->count()->values(),
            'planUsageLabels' => SubscriptionPlan::withCount('subscriptions')->orderBy('display_order')->pluck('name'),
            'planUsageValues' => SubscriptionPlan::withCount('subscriptions')->orderBy('display_order')->pluck('subscriptions_count'),
        ]);
    }
}
