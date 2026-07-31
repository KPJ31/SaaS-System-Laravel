<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CompanyRegistrationController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\CompanyAdmin\DashboardController as CompanyAdminDashboardController;
use App\Http\Controllers\Employee\DashboardController as EmployeeDashboardController;
use App\Http\Controllers\SuperAdmin\CompanyController;
use App\Http\Controllers\SuperAdmin\CompanyRequestController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicPageController::class, 'landing'])->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
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
    Route::post('/companies/{company}/{status}', [CompanyController::class, 'updateStatus'])->name('companies.status');
});

Route::middleware(['auth', 'role:company_admin', 'company.approved'])->prefix('company-admin')->name('company-admin.')->group(function (): void {
    Route::get('/dashboard', CompanyAdminDashboardController::class)->name('dashboard');
});

Route::middleware(['auth', 'role:employee', 'company.approved'])->prefix('employee')->name('employee.')->group(function (): void {
    Route::get('/dashboard', EmployeeDashboardController::class)->name('dashboard');
});
