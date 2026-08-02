<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\CompanyRegistrationController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\CompanyAdmin\DashboardController as CompanyAdminDashboardController;
use App\Http\Controllers\Employee\DashboardController as EmployeeDashboardController;
use App\Http\Controllers\SuperAdmin\CompanyController;
use App\Http\Controllers\SuperAdmin\CompanyRequestController;
use App\Http\Controllers\SuperAdmin\CompanySubscriptionController;
use App\Http\Controllers\SuperAdmin\AuditLogController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\NotificationController;
use App\Http\Controllers\SuperAdmin\PaymentController;
use App\Http\Controllers\SuperAdmin\ProfileController;
use App\Http\Controllers\SuperAdmin\ReportController;
use App\Http\Controllers\SuperAdmin\SettingController;
use App\Http\Controllers\SuperAdmin\SubscriptionPlanController;
use App\Http\Controllers\SuperAdmin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicPageController::class, 'landing'])->name('home');

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
});

Route::middleware(['auth', 'role:employee', 'company.approved', 'subscription.active'])->prefix('employee')->name('employee.')->group(function (): void {
    Route::get('/dashboard', EmployeeDashboardController::class)->name('dashboard');
});
