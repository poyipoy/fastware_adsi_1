<?php

namespace App\Providers;

use App\View\Composers\HRMenuComposer;
use App\View\Composers\DashboardMenuComposer;
use App\View\Composers\SalesMenuComposer;
use App\View\Composers\ProductionsMenuComposer;
use App\View\Composers\ProcurementMenuComposer;
use App\View\Composers\CrpMenuComposer;
use App\View\Composers\SumbangSaranMenuComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Register HR Menu Composer for layout
        View::composer('layout', HRMenuComposer::class);
        
        // Register Dashboard Menu Composer for layout
        View::composer(['layout', '4layout'], DashboardMenuComposer::class);
        
        // Register Sales Menu Composer for layout
        View::composer('layout', SalesMenuComposer::class);
        
        // Register Productions Menu Composer for layout
        View::composer('layout', ProductionsMenuComposer::class);
        
        // Register Procurement Menu Composer for layout
        View::composer('layout', ProcurementMenuComposer::class);
        
        // Register CRP Menu Composer for layout
        View::composer('layout', CrpMenuComposer::class);
        
        // Register Sumbang Saran Menu Composer for layout
        View::composer('layout', SumbangSaranMenuComposer::class);
    }
}
