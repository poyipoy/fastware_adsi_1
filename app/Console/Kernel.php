<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('km:send-approval-reminders')
            ->weekdays()
            ->at('08:00')
            ->withoutOverlapping();

        $schedule->command('km:reconcile-points')
            ->dailyAt('08:15')
            ->withoutOverlapping();

        $schedule->command('km:process-pending-documents --limit=1')
            ->everyMinute()
            ->withoutOverlapping();

        $schedule->command('km:cleanup-temporary-files')
            ->hourly()
            ->withoutOverlapping();

        $schedule->command('km:dispatch-publication-notifications --limit=5')
            ->everyFiveMinutes()
            ->withoutOverlapping();

        $schedule->command('km:send-assignment-reminders')
            ->dailyAt('08:30')
            ->withoutOverlapping();

        $schedule->command('km:sync-hris --limit=50')
            ->hourly()
            ->when(static fn (): bool => (bool) config('knowledge_management.hris.enabled', false)
                && collect(config('knowledge_management.hris.gates', []))->every(static fn ($gate): bool => (bool) $gate))
            ->withoutOverlapping();
    }

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    // protected $routeMiddleware = [
    //     // ...
    //     'auth' => [
    //         \App\Http\Middleware\EncryptCookies::class,
    //         \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    //         \Illuminate\Session\Middleware\StartSession::class,
    //         \Illuminate\Session\Middleware\AuthenticateSession::class,
    //         \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    //         \App\Http\Middleware\VerifyCsrfToken::class,
    //         \Illuminate\Routing\Middleware\SubstituteBindings::class,
    //         \App\Http\Middleware\AuthRedirectMiddleware::class
    //     ],
    //     // 'auth.redirect' => \App\Http\Middleware\AuthRedirectMiddleware::class,
    // ];

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
