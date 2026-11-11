<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     * All commands are idempotent so overlapping or repeated runs are safe.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('nonikos:mark-overdue')->dailyAt('01:00')->withoutOverlapping();
        $schedule->command('nonikos:apply-late-fees')->dailyAt('01:30')->withoutOverlapping();
        $schedule->command('nonikos:expire-agreements')->dailyAt('02:00')->withoutOverlapping();
        $schedule->command('nonikos:generate-invoices')->monthlyOn(25, '03:00')->withoutOverlapping();
        $schedule->command('nonikos:send-reminders --days=3')->dailyAt('08:00')->withoutOverlapping();
        $schedule->command('nonikos:notify-ending-contracts --days=30')->weeklyOn(1, '09:00')->withoutOverlapping();
        $schedule->command('nonikos:backup')->dailyAt('23:30')->withoutOverlapping();
        $schedule->command('nonikos:prune-backups --days=30')->weekly()->sundays()->at('23:45')->withoutOverlapping();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}