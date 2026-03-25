<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

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
        if (config('app.force_https')) {
            URL::forceScheme('https');
        }

        Gate::define('manage-admins', function (User $user) {
            return $user->isSuperAdmin();
        });

        Gate::define('access-dashboard', function (User $user) {
            return $user->hasPermission('dashboard');
        });

        Gate::define('access-documentation', function (User $user) {
            return $user->hasPermission('documentation');
        });

        Gate::define('access-smart-scan', function (User $user) {
            return $user->hasPermission('smart_scan');
        });

        Gate::define('access-ai-consumption', function (User $user) {
            return $user->isSuperAdmin() || $user->hasPermission('ai_consumption');
        });
    }
}
