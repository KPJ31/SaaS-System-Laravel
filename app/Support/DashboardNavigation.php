<?php

namespace App\Support;

use App\Models\CompanyRegistrationRequest;
use App\Models\Invoice;
use App\Models\LeaveRequest;
use App\Models\Payment;
use App\Models\ProjectRequest;
use App\Models\Subscription;
use App\Models\Task;
use App\Models\User;

class DashboardNavigation
{
    public static function forUser(User $user): array
    {
        return [
            'roleLabel' => self::roleLabel($user),
            'summary' => self::summary($user),
            'warning' => self::subscriptionWarning($user),
            'groups' => match ($user->role) {
                'super_admin' => self::superAdmin($user),
                'company_admin' => self::companyAdmin($user),
                default => self::employee($user),
            },
            'footer' => self::footer($user),
        ];
    }

    private static function superAdmin(User $user): array
    {
        $pendingRequests = CompanyRegistrationRequest::where('status', 'pending')->count();
        $expiringSubscriptions = Subscription::whereDate('renews_at', '>=', today())
            ->whereDate('renews_at', '<=', today()->addDays(30))
            ->count();
        $pendingPayments = Payment::where('payment_type', 'subscription')
            ->whereIn('status', ['pending', 'requested', 'proof_submitted'])
            ->count();

        return self::cleanGroups([
            self::group('Overview', [
                self::item('Dashboard', 'fa-gauge-high', 'super-admin.dashboard', 'super-admin.dashboard'),
            ]),
            self::group('Platform Management', [
                self::item('Companies', 'fa-building', 'super-admin.companies.index', 'super-admin.companies.*', null, [
                    self::item('All Companies', 'fa-building', 'super-admin.companies.index', 'super-admin.companies.*'),
                    self::item('Registration Requests', 'fa-building-circle-check', 'super-admin.company-requests.index', 'super-admin.company-requests.*', $pendingRequests),
                ]),
                self::item('Subscriptions', 'fa-credit-card', 'super-admin.subscriptions.index', ['super-admin.subscriptions.*', 'super-admin.subscription-plans.*'], null, [
                    self::item('Subscription Plans', 'fa-layer-group', 'super-admin.subscription-plans.index', 'super-admin.subscription-plans.*'),
                    self::item('Company Subscriptions', 'fa-arrows-rotate', 'super-admin.subscriptions.index', 'super-admin.subscriptions.*'),
                    self::item('Expiring Subscriptions', 'fa-calendar-days', 'super-admin.reports.show', 'super-admin.reports.show', $expiringSubscriptions, [], ['report' => 'subscription-expiry']),
                    self::item('Subscription Payments', 'fa-money-check-dollar', 'super-admin.payments.index', 'super-admin.payments.*', $pendingPayments),
                ]),
            ]),
            self::group('User and Operations', [
                self::item('Platform Users', 'fa-users', 'super-admin.users.index', 'super-admin.users.*'),
            ]),
            self::group('Finance', [
                self::item('Revenue', 'fa-money-bill-wave', 'super-admin.payments.index', ['super-admin.payments.*'], null, [
                    self::item('Revenue Overview', 'fa-chart-line', 'super-admin.reports.show', 'super-admin.reports.show', null, [], ['report' => 'revenue']),
                    self::item('Platform Payments', 'fa-money-bill-wave', 'super-admin.payments.index', 'super-admin.payments.*'),
                ]),
            ]),
            self::group('Insights', [
                self::item('Reports and Analytics', 'fa-chart-column', 'super-admin.reports.index', 'super-admin.reports.*'),
            ]),
            self::group('Administration', [
                self::item('Notifications', 'fa-bell', 'super-admin.notifications.index', 'super-admin.notifications.*', $user->unreadNotifications()->count()),
                self::item('Audit Logs', 'fa-clipboard-list', 'super-admin.audit-logs.index', 'super-admin.audit-logs.*'),
                self::item('System Settings', 'fa-gear', 'super-admin.settings.index', 'super-admin.settings.*'),
            ]),
            self::group('Account', [
                self::item('My Profile', 'fa-user-circle', 'super-admin.profile.show', 'super-admin.profile.*'),
            ]),
        ]);
    }

    private static function companyAdmin(User $user): array
    {
        $companyId = (int) $user->company_id;
        $counts = self::companyCounts($companyId, $user);

        return self::cleanGroups([
            self::group('Overview', [
                self::item('Dashboard', 'fa-gauge-high', 'company-admin.dashboard', 'company-admin.dashboard'),
            ]),
            self::group('Organization', [
                self::item('Company', 'fa-building', 'company-admin.company-profile.show', ['company-admin.company-profile.*', 'company-admin.settings.*'], null, [
                    self::item('Company Profile', 'fa-building', 'company-admin.company-profile.show', 'company-admin.company-profile.*'),
                    self::item('Company Settings', 'fa-gear', 'company-admin.settings.index', 'company-admin.settings.*'),
                ]),
                self::item('Employees', 'fa-user-tie', 'company-admin.employees.index', ['company-admin.employees.*', 'company-admin.performance.*'], $counts['pendingEmployees'], [
                    self::item('All Employees', 'fa-users', 'company-admin.employees.index', 'company-admin.employees.index'),
                    self::item('Add Employee', 'fa-user-plus', 'company-admin.employees.create', 'company-admin.employees.create'),
                    self::item('Employee Permissions', 'fa-shield-halved', 'company-admin.employees.permissions.index', 'company-admin.employees.permissions.*'),
                    self::item('Permission Summary', 'fa-list-check', 'company-admin.employees.permissions.index', 'company-admin.employees.permissions.index'),
                    self::item('Employee Performance', 'fa-chart-line', 'company-admin.reports.show', 'company-admin.reports.show', null, [], ['report' => 'employee-performance']),
                ]),
                self::item('Clients', 'fa-address-book', 'company-admin.clients.index', 'company-admin.clients.*', null, [
                    self::item('All Clients', 'fa-address-book', 'company-admin.clients.index', 'company-admin.clients.index'),
                    self::item('Add Client', 'fa-plus', 'company-admin.clients.create', 'company-admin.clients.create'),
                ]),
            ]),
            self::group('Work Management', [
                self::item('Project Requests', 'fa-inbox', 'company-admin.project-requests.index', 'company-admin.project-requests.*', $counts['pendingProjectRequests'], [
                    self::item('All Requests', 'fa-inbox', 'company-admin.project-requests.index', 'company-admin.project-requests.*'),
                    self::item('Pending Review', 'fa-hourglass-half', 'company-admin.project-requests.index', 'company-admin.project-requests.index', $counts['pendingProjectRequests'], [], ['status' => 'pending']),
                    self::item('Approved Requests', 'fa-circle-check', 'company-admin.project-requests.index', 'company-admin.project-requests.index', null, [], ['status' => 'approved']),
                    self::item('Rejected Requests', 'fa-ban', 'company-admin.project-requests.index', 'company-admin.project-requests.index', null, [], ['status' => 'rejected']),
                ]),
                self::item('Projects', 'fa-folder-open', 'company-admin.projects.index', 'company-admin.projects.*', null, [
                    self::item('All Projects', 'fa-folder-open', 'company-admin.projects.index', 'company-admin.projects.index'),
                    self::item('Create Project', 'fa-plus', 'company-admin.projects.create', 'company-admin.projects.create'),
                    self::item('Active Projects', 'fa-bolt', 'company-admin.projects.index', 'company-admin.projects.index', null, [], ['status' => 'active']),
                    self::item('Completed Projects', 'fa-circle-check', 'company-admin.projects.index', 'company-admin.projects.index', null, [], ['status' => 'completed']),
                ]),
                self::item('Tasks', 'fa-list-check', 'company-admin.tasks.index', 'company-admin.tasks.*', $counts['overdueTasks'] + $counts['submittedTasks'], [
                    self::item('All Tasks', 'fa-list-check', 'company-admin.tasks.index', 'company-admin.tasks.index'),
                    self::item('Create Task', 'fa-plus', 'company-admin.tasks.create', 'company-admin.tasks.create'),
                    self::item('Pending Tasks', 'fa-hourglass-half', 'company-admin.tasks.index', 'company-admin.tasks.index', null, [], ['status' => 'todo']),
                    self::item('Overdue Tasks', 'fa-triangle-exclamation', 'company-admin.reports.show', 'company-admin.reports.show', $counts['overdueTasks'], [], ['report' => 'overdue-tasks']),
                    self::item('Submitted for Review', 'fa-paper-plane', 'company-admin.tasks.index', 'company-admin.tasks.index', $counts['submittedTasks'], [], ['status' => 'submitted']),
                ]),
                self::item('Work Sessions', 'fa-clock', 'company-admin.work-sessions.index', 'company-admin.work-sessions.*', $counts['runningTimers'], [
                    self::item('Work Sessions', 'fa-clock', 'company-admin.work-sessions.index', 'company-admin.work-sessions.*'),
                    self::item('Running Timers', 'fa-stopwatch', 'company-admin.work-sessions.index', 'company-admin.work-sessions.index', $counts['runningTimers'], [], ['status' => 'running']),
                    self::item('Time Reports', 'fa-chart-line', 'company-admin.reports.show', 'company-admin.reports.show', null, [], ['report' => 'work-hours']),
                ]),
                self::item('Leave Requests', 'fa-calendar-check', 'company-admin.leave-requests.index', 'company-admin.leave-requests.*', $counts['pendingLeaveRequests'], [
                    self::item('All Leave Requests', 'fa-calendar-check', 'company-admin.leave-requests.index', 'company-admin.leave-requests.*'),
                    self::item('Pending Requests', 'fa-hourglass-half', 'company-admin.leave-requests.index', 'company-admin.leave-requests.index', $counts['pendingLeaveRequests'], [], ['status' => 'pending']),
                    self::item('Approved Requests', 'fa-circle-check', 'company-admin.leave-requests.index', 'company-admin.leave-requests.index', null, [], ['status' => 'approved']),
                ]),
                self::item('Documents', 'fa-folder-open', 'company-admin.documents.index', 'company-admin.documents.*'),
            ]),
            self::group('Finance', [
                self::item('Payments', 'fa-money-bill-wave', 'company-admin.payments.index', 'company-admin.payments.*', $counts['pendingPayments'], [
                    self::item('All Payments', 'fa-money-bill-wave', 'company-admin.payments.index', 'company-admin.payments.index'),
                    self::item('Payment Requests', 'fa-money-check-dollar', 'company-admin.payments.create', 'company-admin.payments.create'),
                    self::item('Pending Verification', 'fa-circle-question', 'company-admin.payments.index', 'company-admin.payments.index', $counts['pendingPayments'], [], ['status' => 'pending']),
                ]),
                self::item('Invoices', 'fa-file-invoice-dollar', 'company-admin.invoices.index', 'company-admin.invoices.*', $counts['overdueInvoices'], [
                    self::item('All Invoices', 'fa-file-invoice-dollar', 'company-admin.invoices.index', 'company-admin.invoices.index'),
                    self::item('Create Invoice', 'fa-plus', 'company-admin.invoices.create', 'company-admin.invoices.create'),
                    self::item('Overdue Invoices', 'fa-triangle-exclamation', 'company-admin.invoices.index', 'company-admin.invoices.index', $counts['overdueInvoices'], [], ['status' => 'overdue']),
                ]),
            ]),
            self::group('Insights', [
                self::item('Feedback', 'fa-star', 'company-admin.feedback.index', 'company-admin.feedback.*'),
                self::item('Reports and Analytics', 'fa-chart-column', 'company-admin.reports.index', 'company-admin.reports.*'),
                self::item('Activity Logs', 'fa-clipboard-list', 'company-admin.activity-logs.index', 'company-admin.activity-logs.*'),
            ]),
            self::group('Communication', [
                self::item('Notifications', 'fa-bell', 'company-admin.notifications.index', 'company-admin.notifications.*', $counts['unreadNotifications']),
            ]),
            self::group('Account', [
                self::item('My Profile', 'fa-user-circle', 'company-admin.profile.show', 'company-admin.profile.*'),
            ]),
        ]);
    }

    private static function employee(User $user): array
    {
        $counts = self::companyCounts((int) $user->company_id, $user);
        $ownCounts = [
            'myTasks' => Task::where('company_id', $user->company_id)->where('assignee_id', $user->id)->whereIn('status', ['todo', 'assigned', 'in_progress', 'paused', 'blocked'])->count(),
            'activeTimer' => \App\Models\WorkSession::where('company_id', $user->company_id)->where('user_id', $user->id)->whereNull('ended_at')->count(),
            'pendingLeaves' => LeaveRequest::where('company_id', $user->company_id)->where('user_id', $user->id)->where('status', 'pending')->count(),
        ];

        return self::cleanGroups([
            self::group('Overview', [
                self::item('Dashboard', 'fa-gauge-high', 'employee.dashboard', 'employee.dashboard'),
            ]),
            self::group('My Work', [
                self::item('My Projects', 'fa-folder-open', 'employee.projects.index', 'employee.projects.*'),
                self::item('My Tasks', 'fa-list-check', 'employee.tasks.index', 'employee.tasks.*', $ownCounts['myTasks'], [
                    self::item('All My Tasks', 'fa-list-check', 'employee.tasks.index', 'employee.tasks.index'),
                    self::item('Today\'s Tasks', 'fa-calendar-day', 'employee.tasks.index', 'employee.tasks.index', null, [], ['due' => 'today']),
                    self::item('Overdue Tasks', 'fa-triangle-exclamation', 'employee.tasks.index', 'employee.tasks.index', null, [], ['due' => 'overdue']),
                    self::item('Submitted Tasks', 'fa-paper-plane', 'employee.tasks.index', 'employee.tasks.index', null, [], ['status' => 'submitted']),
                ]),
                self::item('Time Tracking', 'fa-clock', 'employee.work-sessions.index', ['employee.work-sessions.*', 'employee.tasks.show'], $ownCounts['activeTimer'], [
                    self::item('Active Timer', 'fa-stopwatch', 'employee.tasks.index', 'employee.tasks.show', $ownCounts['activeTimer']),
                    self::item('My Work Sessions', 'fa-clock', 'employee.work-sessions.index', 'employee.work-sessions.*'),
                ]),
                self::item('My Documents', 'fa-folder-open', 'employee.documents.index', 'employee.documents.*'),
            ]),
            self::group('Company Operations', [
                self::whenCanAny($user, ['clients.view', 'clients.create', 'clients.edit', 'clients.delete'], self::item('Clients', 'fa-address-book', 'employee.clients.index', 'employee.clients.*')),
            ]),
            self::group('Insights', [
                self::whenCanAny($user, ['reports.view', 'reports.export'], self::item('Reports and Analytics', 'fa-chart-column', 'employee.reports.index', 'employee.reports.*')),
            ]),
            self::group('Self-Service', [
                self::item('Leave Requests', 'fa-calendar-check', 'employee.leave-requests.index', 'employee.leave-requests.*', $ownCounts['pendingLeaves']),
                self::item('My Performance', 'fa-chart-line', 'employee.performance.index', 'employee.performance.*'),
                self::item('Notifications', 'fa-bell', 'employee.notifications.index', 'employee.notifications.*', $counts['unreadNotifications']),
                self::item('Activity History', 'fa-clipboard-list', 'employee.activity.index', 'employee.activity.*'),
            ]),
            self::group('Account', [
                self::item('My Profile', 'fa-user-circle', 'employee.profile.show', 'employee.profile.*'),
                self::item('Change Password', 'fa-key', 'employee.password.edit', 'employee.password.*'),
            ]),
        ]);
    }

    private static function companyCounts(int $companyId, User $user): array
    {
        return [
            'pendingEmployees' => User::where('company_id', $companyId)->where('role', 'employee')->where('status', 'pending')->count(),
            'pendingProjectRequests' => ProjectRequest::where('company_id', $companyId)->whereIn('status', ['pending', 'under_review'])->count(),
            'overdueTasks' => Task::where('company_id', $companyId)->whereDate('due_date', '<', today())->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'submittedTasks' => Task::where('company_id', $companyId)->whereIn('status', ['submitted', 'under_review'])->count(),
            'runningTimers' => \App\Models\WorkSession::where('company_id', $companyId)->whereNull('ended_at')->count(),
            'pendingLeaveRequests' => LeaveRequest::where('company_id', $companyId)->where('status', 'pending')->count(),
            'pendingPayments' => Payment::where('company_id', $companyId)->where('payment_type', 'client_project')->whereIn('status', ['pending', 'requested', 'proof_submitted'])->count(),
            'overdueInvoices' => Invoice::where('company_id', $companyId)->where('status', 'overdue')->count(),
            'unreadNotifications' => $user->unreadNotifications()->count(),
        ];
    }

    private static function item(string $label, string $icon, string $route, string|array $active, ?int $badge = null, array $children = [], array $params = []): array
    {
        $isActive = request()->routeIs(...(array) $active);

        if ($isActive && $params !== []) {
            foreach ($params as $key => $value) {
                $currentValue = request($key) ?? request()->route($key);

                if ((string) $currentValue !== (string) $value) {
                    $isActive = false;
                    break;
                }
            }
        } elseif ($isActive && $params === [] && request()->query() !== [] && str_ends_with($route, '.index')) {
            $isActive = false;
        }

        return [
            'label' => $label,
            'icon' => $icon,
            'url' => route($route, $params),
            'route' => $route,
            'active' => $isActive,
            'badge' => self::badge($badge),
            'children' => self::cleanItems($children),
        ];
    }

    private static function group(string $label, array $items): array
    {
        return ['label' => $label, 'items' => self::cleanItems($items)];
    }

    private static function cleanGroups(array $groups): array
    {
        return array_values(array_filter($groups, fn (array $group): bool => count($group['items']) > 0));
    }

    private static function cleanItems(array $items): array
    {
        return array_values(array_filter($items));
    }

    private static function whenCanAny(User $user, array $permissions, array $item): ?array
    {
        return $user->canAnyPermission($permissions) ? $item : null;
    }

    private static function badge(?int $count): ?string
    {
        if (! $count || $count < 1) {
            return null;
        }

        return $count > 99 ? '99+' : (string) $count;
    }

    private static function roleLabel(User $user): string
    {
        return match ($user->role) {
            'super_admin' => 'Super Admin',
            'company_admin' => 'Company Admin',
            default => 'Employee',
        };
    }

    private static function summary(User $user): array
    {
        if ($user->role === 'company_admin') {
            return [
                'title' => $user->company?->name ?? $user->name,
                'subtitle' => 'Company Admin',
                'image' => $user->company?->logo_path,
                'fallback' => $user->company?->name ?? $user->name,
            ];
        }

        return [
            'title' => $user->name,
            'subtitle' => $user->role === 'employee' ? ($user->job_title ?: 'Employee') : 'Super Admin',
            'image' => $user->avatar,
            'fallback' => $user->name,
        ];
    }

    private static function footer(User $user): array
    {
        return [
            self::item('Visit Website', 'fa-globe', 'home', 'home'),
        ];
    }

    private static function subscriptionWarning(User $user): ?array
    {
        if (! in_array($user->role, ['company_admin', 'employee'], true)) {
            return null;
        }

        $subscription = $user->company?->activeSubscription;
        $date = $subscription?->ends_at ?? $subscription?->renews_at ?? $subscription?->trial_ends_at;

        if (! $subscription || ! $date) {
            return null;
        }

        $days = today()->diffInDays($date, false);

        if ($days > 7) {
            return null;
        }

        if ($days < 0) {
            $message = 'Subscription expired';
        } elseif ($subscription->status === 'trialing') {
            $message = 'Trial expires in '.$days.' days';
        } else {
            $message = 'Subscription renews in '.$days.' days';
        }

        return [
            'message' => $user->role === 'employee' ? $message.'. Contact your Company Admin.' : $message,
            'url' => $user->role === 'company_admin' ? route('company-admin.company-profile.show') : null,
        ];
    }
}
