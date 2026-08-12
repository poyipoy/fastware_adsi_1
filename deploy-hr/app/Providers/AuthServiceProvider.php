<?php

namespace App\Providers;

use App\Models\KmPengajuan;
use App\Policies\KmPengajuanPolicy;
use App\Services\HR\TcpdDashboardAccessService;
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
        KmPengajuan::class => KmPengajuanPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::define('viewTcpdDashboard', fn ($user) => app(TcpdDashboardAccessService::class)->canView($user));
        Gate::define('clearTcpdDashboardCache', fn ($user) => app(TcpdDashboardAccessService::class)->canClearCache($user));
    }
}
