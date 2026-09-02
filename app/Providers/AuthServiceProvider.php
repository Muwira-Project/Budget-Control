<?php

namespace App\Providers;

use App\Models\Cashflow;
use App\Models\ProjectAkun;
use App\Models\User;
use App\Policies\CashflowPolicy;
use App\Policies\ProjectAkunPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        ProjectAkun::class => ProjectAkunPolicy::class,
        Cashflow::class => CashflowPolicy::class,
    ];

    /**
     * Register any application authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability): ?bool {
            return $user->isAdmin() ? true : null;
        });
    }
}
