<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Console\ServeCommand;
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
        if (class_exists(ServeCommand::class)) {
            ServeCommand::$passthroughVariables[] = 'TMPDIR';
        }

        Gate::before(function (User $user, string $ability) {
            if ($user->hasRole('super-admin') || $user->hasRole('super_admin') || $user->hasRole('admin')) {
                return true;
            }

            if ($user->hasPermission($ability)) {
                return true;
            }

            return null;
        });
    }
}
