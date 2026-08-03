<?php

namespace App\Support;

class PermissionCatalog
{
    public static function groups(): array
    {
        return [
            'dashboard' => [
                'title' => 'Dashboard',
                'description' => 'Company dashboard shortcuts and workspace overview.',
                'permissions' => [
                    'dashboard.view' => 'View company dashboard',
                ],
            ],
            'employees' => [
                'title' => 'Employees',
                'description' => 'View and manage employee records.',
                'permissions' => [
                    'employees.view' => 'View employees',
                    'employees.create' => 'Create employees',
                    'employees.edit' => 'Edit employees',
                    'employees.suspend' => 'Suspend employees',
                ],
            ],
            'clients' => [
                'title' => 'Clients',
                'description' => 'Manage client profiles and client status.',
                'permissions' => [
                    'clients.view' => 'View clients',
                    'clients.create' => 'Create clients',
                    'clients.edit' => 'Edit clients',
                    'clients.delete' => 'Deactivate clients',
                ],
            ],
            'project-requests' => [
                'title' => 'Project Requests',
                'description' => 'Review and convert incoming project requests.',
                'permissions' => [
                    'project-requests.view' => 'View project requests',
                    'project-requests.review' => 'Review project requests',
                    'project-requests.approve' => 'Approve project requests',
                    'project-requests.reject' => 'Reject project requests',
                    'project-requests.convert' => 'Convert requests to projects',
                ],
            ],
            'projects' => [
                'title' => 'Projects',
                'description' => 'Create, update, assign and track company projects.',
                'permissions' => [
                    'projects.view' => 'View projects',
                    'projects.create' => 'Create projects',
                    'projects.edit' => 'Edit projects',
                    'projects.delete' => 'Cancel projects',
                    'projects.assign-employees' => 'Assign employees',
                    'projects.update-status' => 'Update project status',
                    'projects.manage-files' => 'Manage project files',
                ],
            ],
            'tasks' => [
                'title' => 'Tasks',
                'description' => 'Manage company tasks and submitted work.',
                'permissions' => [
                    'tasks.view' => 'View tasks',
                    'tasks.create' => 'Create tasks',
                    'tasks.edit' => 'Edit tasks',
                    'tasks.delete' => 'Cancel tasks',
                    'tasks.assign' => 'Assign tasks',
                    'tasks.review' => 'Review submitted tasks',
                    'tasks.complete' => 'Complete tasks',
                    'tasks.manage-files' => 'Manage task files',
                    'tasks.manage-comments' => 'Manage task comments',
                ],
            ],
            'work-sessions' => [
                'title' => 'Work Sessions',
                'description' => 'View time tracking and adjust approved records.',
                'permissions' => [
                    'work-sessions.view-all' => 'View all employee work sessions',
                    'work-sessions.adjust' => 'Adjust work sessions',
                    'work-sessions.export' => 'Export work-session reports',
                ],
            ],
            'attendance' => [
                'title' => 'Attendance',
                'description' => 'View, export and correct company attendance records.',
                'permissions' => [
                    'attendance.view-all' => 'View all attendance records',
                    'attendance.edit' => 'Correct attendance records',
                    'attendance.export' => 'Export attendance reports',
                    'attendance.reports' => 'View attendance reports',
                ],
            ],
            'leave-requests' => [
                'title' => 'Leave Requests',
                'description' => 'Review leave requests from employees.',
                'permissions' => [
                    'leave-requests.view-all' => 'View all leave requests',
                    'leave-requests.approve' => 'Approve leave requests',
                    'leave-requests.reject' => 'Reject leave requests',
                ],
            ],
            'payments' => [
                'title' => 'Payments',
                'description' => 'View and verify project payment records.',
                'permissions' => [
                    'payments.view' => 'View payments',
                    'payments.create' => 'Create payment requests',
                    'payments.verify' => 'Verify payments',
                    'payments.reject' => 'Reject payments',
                    'payments.refund' => 'Refund payments',
                ],
            ],
            'invoices' => [
                'title' => 'Invoices',
                'description' => 'Create, send and print client invoices.',
                'permissions' => [
                    'invoices.view' => 'View invoices',
                    'invoices.create' => 'Create invoices',
                    'invoices.edit' => 'Edit invoices',
                    'invoices.send' => 'Send invoices',
                    'invoices.print' => 'Print invoices',
                ],
            ],
            'feedback' => [
                'title' => 'Feedback',
                'description' => 'Moderate client feedback.',
                'permissions' => [
                    'feedback.view' => 'View feedback',
                    'feedback.approve' => 'Approve feedback',
                    'feedback.hide' => 'Hide feedback',
                ],
            ],
            'reports' => [
                'title' => 'Reports',
                'description' => 'View and export company reports.',
                'permissions' => [
                    'reports.view' => 'View reports',
                    'reports.export' => 'Export reports',
                ],
            ],
            'notifications' => [
                'title' => 'Notifications',
                'description' => 'View and manage company notifications.',
                'permissions' => [
                    'notifications.view' => 'View notifications',
                    'notifications.manage' => 'Manage notifications',
                ],
            ],
            'activity-logs' => [
                'title' => 'Activity Logs',
                'description' => 'View company activity and audit history.',
                'permissions' => [
                    'activity-logs.view' => 'View activity logs',
                ],
            ],
            'company-profile' => [
                'title' => 'Company Profile',
                'description' => 'View and edit company public profile data.',
                'permissions' => [
                    'company-profile.view' => 'View company profile',
                    'company-profile.edit' => 'Edit company profile',
                ],
            ],
            'company-settings' => [
                'title' => 'Company Settings',
                'description' => 'View and update company settings.',
                'permissions' => [
                    'company-settings.view' => 'View company settings',
                    'company-settings.edit' => 'Update company settings',
                ],
            ],
        ];
    }

    public static function assignableNames(): array
    {
        return array_keys(self::labels());
    }

    public static function labels(): array
    {
        $labels = [];

        foreach (self::groups() as $group) {
            $labels += $group['permissions'];
        }

        return $labels;
    }

    public static function moduleFor(string $permission): ?string
    {
        foreach (self::groups() as $key => $group) {
            if (array_key_exists($permission, $group['permissions'])) {
                return $key;
            }
        }

        return null;
    }

    public static function basicEmployeeNames(): array
    {
        return [
            'employee.dashboard',
            'projects.view-assigned',
            'tasks.view-assigned',
            'tasks.update-own',
            'work-sessions.view-own',
            'work-sessions.manage-own',
            'attendance.view-own',
            'attendance.check-in',
            'attendance.check-out',
            'leave-requests.view-own',
            'leave-requests.create',
            'notifications.view-own',
            'profile.manage-own',
        ];
    }

    public static function platformNames(): array
    {
        return [
            'companies.view-all',
            'companies.approve',
            'companies.reject',
            'companies.suspend',
            'subscriptions.manage-platform',
            'platform-users.view',
            'platform-settings.manage',
            'platform-revenue.view',
            'platform-audit-logs.view',
            'super-admin.dashboard',
        ];
    }

    public static function templates(): array
    {
        return [
            'project_coordinator' => [
                'name' => 'Project Coordinator',
                'permissions' => ['projects.view', 'projects.create', 'projects.edit', 'projects.assign-employees', 'projects.update-status', 'tasks.view', 'tasks.create', 'tasks.edit', 'tasks.assign', 'tasks.review'],
            ],
            'team_lead' => [
                'name' => 'Team Lead',
                'permissions' => ['employees.view', 'projects.view', 'tasks.view', 'tasks.create', 'tasks.edit', 'tasks.assign', 'tasks.review', 'work-sessions.view-all', 'leave-requests.view-all', 'leave-requests.approve', 'reports.view'],
            ],
            'finance_officer' => [
                'name' => 'Finance Officer',
                'permissions' => ['clients.view', 'projects.view', 'payments.view', 'payments.create', 'payments.verify', 'payments.reject', 'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.send', 'reports.view'],
            ],
            'hr_coordinator' => [
                'name' => 'HR Coordinator',
                'permissions' => ['employees.view', 'employees.create', 'employees.edit', 'employees.suspend', 'leave-requests.view-all', 'leave-requests.approve', 'leave-requests.reject', 'reports.view'],
            ],
            'viewer' => [
                'name' => 'Viewer',
                'permissions' => ['employees.view', 'clients.view', 'projects.view', 'tasks.view', 'work-sessions.view-all', 'reports.view'],
            ],
        ];
    }

    public static function isCompanyPermission(string $ability): bool
    {
        return in_array($ability, self::assignableNames(), true)
            || in_array($ability, self::basicEmployeeNames(), true);
    }

    public static function isPlatformPermission(string $ability): bool
    {
        return in_array($ability, self::platformNames(), true);
    }
}
