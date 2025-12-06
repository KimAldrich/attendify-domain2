<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

// Models
use App\Models\Event;

// Policies
use App\Policies\EventPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Event::class => EventPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Global permissions (already created in AppServiceProvider—
        // move them here for proper organization)
        Gate::define('access-manage-events', function ($user) {
            return $user->can('manage events') || $user->can('co-organize events');
        });

        Gate::define('access-event-scanner', function ($user) {
            return $user->can('manage events')
                || $user->can('staff events')
                || $user->can('open scanner');
        });
    }
}
