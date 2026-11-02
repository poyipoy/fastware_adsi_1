<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('km-comments', function (Request $request) {
            return Limit::perMinutes(10, 10)
                ->by('km-comments:'.($request->user()?->id ?: $request->ip()));
        });

        RateLimiter::for('km-reactions', function (Request $request) {
            return Limit::perMinute(30)
                ->by('km-reactions:'.($request->user()?->id ?: $request->ip()));
        });

        RateLimiter::for('warehouse-scan', function (Request $request) {
            return Limit::perMinute((int) config('warehouse.rate_limits.scan_per_minute', 120))
                ->by('warehouse-scan:'.($request->user()?->id ?: $request->ip()));
        });

        RateLimiter::for('warehouse-mutation', function (Request $request) {
            return Limit::perMinute((int) config('warehouse.rate_limits.mutation_per_minute', 30))
                ->by('warehouse-mutation:'.($request->user()?->id ?: $request->ip()));
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
