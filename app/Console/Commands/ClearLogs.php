<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ClearLogs extends Command
{
    protected $signature = 'logs:clear';
    protected $description = 'Clear the application log file';

    public function handle()
    {
        // Clear the log file
        Storage::delete('logs/laravel.log'); // Adjust the path if needed
        $this->info('Log file cleared successfully.');
    }
}
