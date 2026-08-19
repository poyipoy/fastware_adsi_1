<?php

namespace App\Providers;

use App\Models\KmPengajuan;
use App\Policies\KmPengajuanPolicy;
use App\Services\HR\TcpdDashboardAccessService;
use App\Services\Warehouse\WarehouseAccessService;
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

        foreach ([
            'warehouse.dashboard.view',
            'warehouse.stock-in.create',
            'warehouse.stock-out.create',
            'warehouse.master.manage',
            'warehouse.transaction.view',
            'warehouse.transaction.reverse',
            'warehouse.location-shipment.view',
            'warehouse.location-shipment.create',
            'warehouse.location-shipment.validate',
            'warehouse.location-shipment.cancel',
            'warehouse.report.view',
            'warehouse.report.export',
        ] as $ability) {
            Gate::define($ability, fn ($user) => app(WarehouseAccessService::class)->can($user, $ability));
        }
    }
}
