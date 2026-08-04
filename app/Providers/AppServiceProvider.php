<?php

namespace App\Providers;

use App\Support\PermissionCatalog;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('partials.pagination');

        foreach (array_merge(
            PermissionCatalog::assignableNames(),
            PermissionCatalog::basicEmployeeNames(),
            PermissionCatalog::platformNames(),
        ) as $permission) {
            Gate::define($permission, fn ($user): bool => $user->hasCompanyPermission($permission));
        }
    }
}
