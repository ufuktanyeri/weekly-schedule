<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\AdminUser;
use App\Services\AuthService;
use App\Services\TimeSlotService;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            TimeSlotService::class,
            static fn (Application $app): TimeSlotService => TimeSlotService::fromConfig($app->make('config')),
        );

        $this->app->when(AuthService::class)
            ->needs(StatefulGuard::class)
            ->give(static function (Application $app): StatefulGuard {
                $guard = $app->make(AuthFactory::class)->guard('web');
                assert($guard instanceof StatefulGuard);

                return $guard;
            });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('manage-users', static fn (AdminUser $user): bool => $user->isApproved() && $user->role->canManageUsers());
        Gate::define('manage-schedule', static fn (AdminUser $user): bool => $user->isApproved() && $user->role->canManageSchedule());
    }
}
