<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('campaign:process')->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        // Load all console commands
        $this->load(__DIR__.'/Commands');

        // Register the ClearLogs command specifically
        //$this->command('logs:clear', \App\Console\Commands\ClearLogs::class);

        require base_path('routes/console.php');
    }
}
 