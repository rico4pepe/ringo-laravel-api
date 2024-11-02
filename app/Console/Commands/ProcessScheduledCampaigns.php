<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScheduledCampaign;
use App\Import\ExcelCsvImport;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ProcessScheduledCampaigns extends Command
{
    protected $signature = 'campaign:process';
    protected $description = 'Process and send scheduled SMS campaigns.';

    protected $importService;

    public function __construct(ExcelCsvImport $importService)
    {
        parent::__construct();
        $this->importService = $importService;
    }

    public function handle()
    {
        $now = Carbon::now();
        $campaigns = ScheduledCampaign::whereDate('schedule_date', $now->toDateString())
            ->whereTime('schedule_time', '<=', $now->toTimeString())
            ->where('status', 'pending')
            ->get();

        foreach ($campaigns as $campaign) {
            $filePath = storage_path("app/{$campaign->file_path}");

            if (Storage::exists($campaign->file_path)) {
                // Import file and send SMSs
                $summary = $this->importService->importFile($filePath, $campaign->is_custom_message);

                // Update campaign status to completed
                $campaign->update([
                    'status' => 'completed',
                    'summary' => $summary,
                ]);

                // Delete the temporary file
                Storage::delete($campaign->file_path);
            } else {
                $campaign->update(['status' => 'file_missing']);
                $this->error("File for campaign {$campaign->id} is missing.");
            }
        }

        $this->info("Scheduled campaigns processed successfully.");
    }
}
