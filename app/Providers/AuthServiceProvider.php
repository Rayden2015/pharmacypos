<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // After Spatie registers Gate::before, allow platform super admins all abilities.
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            if ($user instanceof \App\Models\User && $user->isSuperAdmin()) {
                return true;
            }
        });
    }
}
