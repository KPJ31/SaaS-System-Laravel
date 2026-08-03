<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\CompanyAdmin\ActivityLogController as CompanyAdminActivityLogController;
use App\Http\Controllers\CompanyAdmin\AttendanceController as CompanyAdminAttendanceController;
use App\Http\Controllers\CompanyAdmin\ClientController as CompanyAdminClientController;
use App\Http\Controllers\CompanyAdmin\CompanyProfileController;
use App\Http\Controllers\CompanyAdmin\DashboardController as CompanyAdminDashboardController;
use App\Http\Controllers\CompanyAdmin\DocumentController as CompanyAdminDocumentController;
use App\Http\Controllers\CompanyAdmin\EmployeeController;
use App\Http\Controllers\CompanyAdmin\EmployeePermissionController;
use App\Http\Controllers\CompanyAdmin\FeedbackController as CompanyAdminFeedbackController;
use App\Http\Controllers\CompanyAdmin\InvoiceController as CompanyAdminInvoiceController;
use App\Http\Controllers\CompanyAdmin\LeaveRequestController as CompanyAdminLeaveRequestController;
use App\Http\Controllers\CompanyAdmin\NotificationController as CompanyAdminNotificationController;
use App\Http\Controllers\CompanyAdmin\PaymentController as CompanyAdminPaymentController;
use App\Http\Controllers\CompanyAdmin\ProfileController as CompanyAdminProfileController;
use App\Http\Controllers\CompanyAdmin\ProjectController as CompanyAdminProjectController;
use App\Http\Controllers\CompanyAdmin\ProjectRequestController as CompanyAdminProjectRequestController;
use App\Http\Controllers\CompanyAdmin\ReportController as CompanyAdminReportController;
use App\Http\Controllers\CompanyAdmin\SettingController as CompanyAdminSettingController;
use App\Http\Controllers\CompanyAdmin\TaskController as CompanyAdminTaskController;
use App\Http\Controllers\CompanyAdmin\WorkSessionController as CompanyAdminWorkSessionController;
use App\Http\Controllers\CompanyRegistrationController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\Employee\ActivityController as EmployeeActivityController;
use App\Http\Controllers\Employee\AttendanceController as EmployeeAttendanceController;
use App\Http\Controllers\Employee\DashboardController as EmployeeDashboardController;
use App\Http\Controllers\Employee\DocumentController as EmployeeDocumentController;
use App\Http\Controllers\Employee\LeaveRequestController as EmployeeLeaveRequestController;
use App\Http\Controllers\Employee\NotificationController as EmployeeNotificationController;
use App\Http\Controllers\Employee\PasswordController as EmployeePasswordController;
use App\Http\Controllers\Employee\PerformanceController as EmployeePerformanceController;
use App\Http\Controllers\Employee\ProfileController as EmployeeProfileController;
use App\Http\Controllers\Employee\ProjectController as EmployeeProjectController;
use App\Http\Controllers\Employee\TaskController as EmployeeTaskController;
use App\Http\Controllers\Employee\WorkSessionController as EmployeeWorkSessionController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\SuperAdmin\AuditLogController;
use App\Http\Controllers\SuperAdmin\CompanyController;
use App\Http\Controllers\SuperAdmin\CompanyRequestController;
use App\Http\Controllers\SuperAdmin\CompanySubscriptionController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\NotificationController;
use App\Http\Controllers\SuperAdmin\PaymentController;
use App\Http\Controllers\SuperAdmin\ProfileController;
use App\Http\Controllers\SuperAdmin\RevenueController;
use App\Http\Controllers\SuperAdmin\ReportController;
use App\Http\Controllers\SuperAdmin\SettingController;
use App\Http\Controllers\SuperAdmin\SubscriptionPlanController;
use App\Http\Controllers\SuperAdmin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicPageController::class, 'landing'])->name('home');
Route::get('/about', [PublicPageController::class, 'about'])->name('about');
Route::get('/contact', [PublicPageController::class, 'contact'])->name('contact');
Route::post('/contact', [PublicPageController::class, 'submitContact'])->name('contact.submit');
Route::get('/privacy-policy', [PublicPageController::class, 'privacy'])->name('privacy');
Route::get('/terms-and-conditions', [PublicPageController::class, 'terms'])->name('terms');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
    Route::get('/company/register', [CompanyRegistrationController::class, 'create'])->name('company.register');
    Route::post('/company/register', [CompanyRegistrationController::class, 'store'])->name('company.register.store');
    Route::get('/company/register/submitted', [CompanyRegistrationController::class, 'submitted'])->name('company.register.submitted');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');
Route::get('/dashboard', DashboardRedirectController::class)->middleware('auth')->name('dashboard');

Route::middleware(['auth', 'role:super_admin'])->prefix('super-admin')->name('super-admin.')->group(function (): void {
    Route::get('/dashboard', SuperAdminDashboardController::class)->name('dashboard');

    Route::get('/company-requests', [CompanyRequestController::class, 'index'])->name('company-requests.index');
    Route::get('/company-requests/{companyRequest}', [CompanyRequestController::class, 'show'])->name('company-requests.show');
    Route::post('/company-requests/{companyRequest}/approve', [CompanyRequestController::class, 'approve'])->name('company-requests.approve');
    Route::post('/company-requests/{companyRequest}/reject', [CompanyRequestController::class, 'reject'])->name('company-requests.reject');

    Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
    Route::get('/companies/{company}', [CompanyController::class, 'show'])->name('companies.show');
    Route::get('/companies/{company}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
    Route::put('/companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
    Route::post('/companies/{company}/{status}', [CompanyController::class, 'updateStatus'])->name('companies.status');

    Route::get('/subscription-plans', [SubscriptionPlanController::class, 'index'])->name('subscription-plans.index');
    Route::get('/subscription-plans/create', [SubscriptionPlanController::class, 'create'])->name('subscription-plans.create');
    Route::post('/subscription-plans', [SubscriptionPlanController::class, 'store'])->name('subscription-plans.store');
    Route::get('/subscription-plans/{subscriptionPlan}', [SubscriptionPlanController::class, 'show'])->name('subscription-plans.show');
    Route::get('/subscription-plans/{subscriptionPlan}/edit', [SubscriptionPlanController::class, 'edit'])->name('subscription-plans.edit');
    Route::put('/subscription-plans/{subscriptionPlan}', [SubscriptionPlanController::class, 'update'])->name('subscription-plans.update');
    Route::post('/subscription-plans/{subscriptionPlan}/{status}', [SubscriptionPlanController::class, 'updateStatus'])->name('subscription-plans.status');

    Route::get('/subscriptions', [CompanySubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::get('/subscriptions/{subscription}', [CompanySubscriptionController::class, 'show'])->name('subscriptions.show');
    Route::put('/subscriptions/{subscription}', [CompanySubscriptionController::class, 'update'])->name('subscriptions.update');
    Route::post('/subscriptions/{subscription}/{status}', [CompanySubscriptionController::class, 'updateStatus'])->name('subscriptions.status');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::post('/users/{user}/{status}', [UserController::class, 'updateStatus'])->name('users.status');
    Route::post('/users/{user}/password-reset', [UserController::class, 'sendPasswordReset'])->name('users.password-reset');

    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
    Route::post('/payments/{payment}/{status}', [PaymentController::class, 'updateStatus'])->name('payments.status');

    Route::get('/revenue/export/csv', [RevenueController::class, 'exportCsv'])->name('revenue.export.csv');
    Route::get('/revenue/export/pdf', [RevenueController::class, 'exportPdf'])->name('revenue.export.pdf');
    Route::get('/revenue', [RevenueController::class, 'index'])->name('revenue.index');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/{report}', [ReportController::class, 'export'])->name('reports.export');
    Route::get('/reports/pdf/{report}', [ReportController::class, 'exportPdf'])->name('reports.pdf');
    Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show');

    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});

Route::middleware(['auth', 'role:company_admin', 'company.approved', 'subscription.active'])->prefix('company-admin')->name('company-admin.')->group(function (): void {
    Route::get('/dashboard', CompanyAdminDashboardController::class)->name('dashboard');

    Route::get('/company-profile', [CompanyProfileController::class, 'show'])->name('company-profile.show');
    Route::get('/company-profile/edit', [CompanyProfileController::class, 'edit'])->name('company-profile.edit');
    Route::put('/company-profile', [CompanyProfileController::class, 'update'])->name('company-profile.update');

    Route::resource('employees', EmployeeController::class);
    Route::get('/employee-permissions', [EmployeePermissionController::class, 'index'])->name('employees.permissions.index');
    Route::get('/employees/{employee}/permissions', [EmployeePermissionController::class, 'edit'])->name('employees.permissions.edit');
    Route::put('/employees/{employee}/permissions', [EmployeePermissionController::class, 'update'])->name('employees.permissions.update');
    Route::post('/employees/{employee}/permissions/reset', [EmployeePermissionController::class, 'reset'])->name('employees.permissions.reset');
    Route::post('/employees/{employee}/permissions/copy', [EmployeePermissionController::class, 'copy'])->name('employees.permissions.copy');
    Route::post('/employees/{employee}/{status}', [EmployeeController::class, 'updateStatus'])->name('employees.status');
    Route::post('/employees/{employee}/password-reset', [EmployeeController::class, 'sendPasswordReset'])->name('employees.password-reset');

    Route::resource('clients', CompanyAdminClientController::class);
    Route::post('/clients/{client}/{status}', [CompanyAdminClientController::class, 'updateStatus'])->name('clients.status');

    Route::get('/project-requests', [CompanyAdminProjectRequestController::class, 'index'])->name('project-requests.index');
    Route::get('/project-requests/{projectRequest}', [CompanyAdminProjectRequestController::class, 'show'])->name('project-requests.show');
    Route::put('/project-requests/{projectRequest}', [CompanyAdminProjectRequestController::class, 'update'])->name('project-requests.update');
    Route::post('/project-requests/{projectRequest}/approve', [CompanyAdminProjectRequestController::class, 'approve'])->name('project-requests.approve');
    Route::post('/project-requests/{projectRequest}/reject', [CompanyAdminProjectRequestController::class, 'reject'])->name('project-requests.reject');
    Route::post('/project-requests/{projectRequest}/convert', [CompanyAdminProjectRequestController::class, 'convertToProject'])->name('project-requests.convert');

    Route::resource('projects', CompanyAdminProjectController::class);
    Route::post('/projects/{project}/assign', [CompanyAdminProjectController::class, 'assignEmployee'])->name('projects.assign');
    Route::delete('/projects/{project}/employees/{employee}', [CompanyAdminProjectController::class, 'removeEmployee'])->name('projects.employees.destroy');

    Route::resource('tasks', CompanyAdminTaskController::class);
    Route::post('/tasks/{task}/{status}', [CompanyAdminTaskController::class, 'updateStatus'])->name('tasks.status');
    Route::patch('/tasks/{task}/review', [CompanyAdminTaskController::class, 'review'])->name('tasks.review');
    Route::post('/tasks/{task}/comments', [CompanyAdminTaskController::class, 'comment'])->name('tasks.comments.store');
    Route::post('/tasks/{task}/files', [CompanyAdminTaskController::class, 'upload'])->name('tasks.files.store');
    Route::get('/files/{file}/download', [CompanyAdminTaskController::class, 'download'])->name('files.download');

    Route::get('/work-sessions', [CompanyAdminWorkSessionController::class, 'index'])->name('work-sessions.index');
    Route::get('/work-sessions/export/csv', [CompanyAdminWorkSessionController::class, 'export'])->name('work-sessions.export');
    Route::get('/work-sessions/export/pdf', [CompanyAdminWorkSessionController::class, 'exportPdf'])->name('work-sessions.pdf');
    Route::patch('/work-sessions/{workSession}', [CompanyAdminWorkSessionController::class, 'update'])->name('work-sessions.update');

    Route::get('/attendance', [CompanyAdminAttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/export/csv', [CompanyAdminAttendanceController::class, 'export'])->name('attendance.export');
    Route::get('/attendance/export/pdf', [CompanyAdminAttendanceController::class, 'exportPdf'])->name('attendance.pdf');
    Route::patch('/attendance/{attendance}', [CompanyAdminAttendanceController::class, 'update'])->name('attendance.update');

    Route::get('/leave-requests', [CompanyAdminLeaveRequestController::class, 'index'])->name('leave-requests.index');
    Route::get('/leave-requests/{leaveRequest}', [CompanyAdminLeaveRequestController::class, 'show'])->name('leave-requests.show');
    Route::patch('/leave-requests/{leaveRequest}/review', [CompanyAdminLeaveRequestController::class, 'review'])->name('leave-requests.review');

    Route::get('/documents', [CompanyAdminDocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [CompanyAdminDocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{file}/download', [CompanyAdminDocumentController::class, 'download'])->name('documents.download');
    Route::delete('/documents/{file}', [CompanyAdminDocumentController::class, 'destroy'])->name('documents.destroy');

    Route::resource('payments', CompanyAdminPaymentController::class);
    Route::post('/payments/{payment}/verify', [CompanyAdminPaymentController::class, 'verify'])->name('payments.verify');
    Route::post('/payments/{payment}/reject', [CompanyAdminPaymentController::class, 'reject'])->name('payments.reject');

    Route::resource('invoices', CompanyAdminInvoiceController::class);
    Route::get('/invoices/{invoice}/print', [CompanyAdminInvoiceController::class, 'print'])->name('invoices.print');
    Route::post('/invoices/{invoice}/send', [CompanyAdminInvoiceController::class, 'send'])->name('invoices.send');
    Route::post('/invoices/{invoice}/paid', [CompanyAdminInvoiceController::class, 'markPaid'])->name('invoices.paid');

    Route::get('/feedback', [CompanyAdminFeedbackController::class, 'index'])->name('feedback.index');
    Route::post('/feedback/{feedback}/{status}', [CompanyAdminFeedbackController::class, 'updateStatus'])->name('feedback.status');

    Route::get('/notifications', [CompanyAdminNotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [CompanyAdminNotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [CompanyAdminNotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    Route::get('/reports', [CompanyAdminReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/{report}', [CompanyAdminReportController::class, 'export'])->name('reports.export');
    Route::get('/reports/pdf/{report}', [CompanyAdminReportController::class, 'exportPdf'])->name('reports.pdf');
    Route::get('/reports/{report}', [CompanyAdminReportController::class, 'show'])->name('reports.show');

    Route::get('/activity-logs', [CompanyAdminActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('/activity-logs/{auditLog}', [CompanyAdminActivityLogController::class, 'show'])->name('activity-logs.show');

    Route::get('/settings', [CompanyAdminSettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [CompanyAdminSettingController::class, 'update'])->name('settings.update');

    Route::get('/profile', [CompanyAdminProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [CompanyAdminProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [CompanyAdminProfileController::class, 'updatePassword'])->name('profile.password');
});

Route::middleware(['auth', 'role:employee', 'employee.active', 'company.approved', 'subscription.active'])->prefix('employee')->name('employee.')->group(function (): void {
    Route::get('/dashboard', EmployeeDashboardController::class)->name('dashboard');
    Route::get('/clients', [CompanyAdminClientController::class, 'index'])->middleware('permission:clients.view')->name('clients.index');
    Route::get('/clients/{client}', [CompanyAdminClientController::class, 'show'])->middleware('permission:clients.view')->name('clients.show');
    Route::get('/reports', [CompanyAdminReportController::class, 'index'])->middleware('permission:reports.view')->name('reports.index');
    Route::get('/reports/export/{report}', [CompanyAdminReportController::class, 'export'])->middleware('permission:reports.export')->name('reports.export');
    Route::get('/reports/pdf/{report}', [CompanyAdminReportController::class, 'exportPdf'])->middleware('permission:reports.export')->name('reports.pdf');
    Route::get('/reports/{report}', [CompanyAdminReportController::class, 'show'])->middleware('permission:reports.view')->name('reports.show');
    Route::get('/projects', [EmployeeProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/{project}', [EmployeeProjectController::class, 'show'])->name('projects.show');
    Route::get('/tasks', [EmployeeTaskController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/{task}', [EmployeeTaskController::class, 'show'])->name('tasks.show');
    Route::post('/tasks/{task}/start', [EmployeeTaskController::class, 'start'])->name('tasks.start');
    Route::post('/tasks/{task}/stop', [EmployeeTaskController::class, 'stop'])->name('tasks.stop');
    Route::patch('/tasks/{task}/progress', [EmployeeTaskController::class, 'progress'])->name('tasks.progress');
    Route::patch('/tasks/{task}/status', [EmployeeTaskController::class, 'status'])->name('tasks.status');
    Route::post('/tasks/{task}/comments', [EmployeeTaskController::class, 'comment'])->name('tasks.comments.store');
    Route::post('/tasks/{task}/files', [EmployeeTaskController::class, 'upload'])->name('tasks.files.store');
    Route::get('/files/{file}/download', [EmployeeTaskController::class, 'download'])->name('files.download');
    Route::get('/work-sessions', [EmployeeWorkSessionController::class, 'index'])->name('work-sessions.index');
    Route::get('/work-sessions/export/csv', [EmployeeWorkSessionController::class, 'export'])->name('work-sessions.export');
    Route::get('/work-sessions/export/pdf', [EmployeeWorkSessionController::class, 'exportPdf'])->name('work-sessions.pdf');
    Route::get('/attendance', [EmployeeAttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/export/csv', [EmployeeAttendanceController::class, 'export'])->name('attendance.export');
    Route::get('/attendance/export/pdf', [EmployeeAttendanceController::class, 'exportPdf'])->name('attendance.pdf');
    Route::post('/attendance/check-in', [EmployeeAttendanceController::class, 'checkIn'])->name('attendance.check-in');
    Route::post('/attendance/check-out', [EmployeeAttendanceController::class, 'checkOut'])->name('attendance.check-out');
    Route::get('/documents', [EmployeeDocumentController::class, 'index'])->name('documents.index');
    Route::resource('leave-requests', EmployeeLeaveRequestController::class)->except(['show', 'destroy']);
    Route::post('/leave-requests/{leaveRequest}/cancel', [EmployeeLeaveRequestController::class, 'cancel'])->name('leave-requests.cancel');
    Route::get('/performance', EmployeePerformanceController::class)->name('performance.index');
    Route::get('/notifications', [EmployeeNotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [EmployeeNotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [EmployeeNotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::get('/activity-history', [EmployeeActivityController::class, 'index'])->name('activity.index');
    Route::get('/profile', [EmployeeProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [EmployeeProfileController::class, 'update'])->name('profile.update');
    Route::get('/password', [EmployeePasswordController::class, 'edit'])->name('password.edit');
    Route::put('/password', [EmployeePasswordController::class, 'update'])->name('password.update');
});
