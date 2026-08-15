<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Llm\LlmManager;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LlmManager::class, fn () => new LlmManager(config('llm')));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Admins bypass all permission checks; returning null lets the
        // normal permission/policy pipeline decide for everyone else.
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole('admin') ? true : null;
        });
    }
}
