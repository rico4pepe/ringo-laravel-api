<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\MessageStat;

class ComputeMessageStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
     protected $signature = 'compute:message-stats';
 
    protected $description = 'Compute and store message delivery stats by network and batch (every 1000 messages)';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
   public function handle()
{
    // Log start
    Log::channel('sms_stats')->info("Starting stats computation at " . now());

    $batchSize = 1000;
    $lastId = 0;
    $batchId = 1;

    do {
        $messages = DB::table('sms')
            ->where('id', '>', $lastId)
            ->orderBy('id')
            ->limit($batchSize)
            ->get();

        if ($messages->isEmpty()) {
            break;
        }

        $stats = [];
        $startId = $messages->first()->id;
        $endId = $messages->last()->id;

        foreach ($messages as $message) {
            $network = $this->resolveNetwork($message->phone_number);
            if (!in_array($network, ['MTN', 'Airtel', 'Glo', '9mobile'])) {
                $network = 'MTN'; // Convert unknowns to MTN as fallback
            }

            if (!isset($stats[$network])) {
                $stats[$network] = [
                    'network' => $network,
                    'batch_id' => $batchId,
                    'total_messages' => 0,
                    'delivered' => 0,
                    'undelivered' => 0,
                    'pending' => 0,
                    'errors' => [],
                    'start_id' => $startId,
                    'end_id' => $endId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $stats[$network]['total_messages']++;

            // Determine delivery status
            $statusCode = (string) ($message->status ?? $message->status_code);
            $errCode = trim((string) $message->err_code);

            if ($statusCode == '1') {
                $stats[$network]['delivered']++;
            } elseif ($statusCode == '2') {
                $stats[$network]['undelivered']++;
                if (!empty($errCode)) {
                    if (!isset($stats[$network]['errors'][$errCode])) {
                        $stats[$network]['errors'][$errCode] = 0;
                    }
                    $stats[$network]['errors'][$errCode]++;
                }
            } else {
                $stats[$network]['pending']++;
            }
        }

        foreach ($stats as $data) {
            $errors = $data['errors'];
            unset($data['errors']);

            MessageStat::updateOrCreate(
                ['network' => $data['network'], 'batch_id' => $data['batch_id']],
                array_merge($data, [
                    'errors' => json_encode($errors),
                ])
            );

            Log::channel('sms_stats')->info('Batch processed', [
                'network' => $data['network'],
                'batch_id' => $data['batch_id'],
                'delivered' => $data['delivered'],
                'undelivered' => $data['undelivered'],
                'pending' => $data['pending'],
                'top_errors' => $errors,
            ]);
        }

        $lastId = $endId;
        $batchId++;

    } while (true);

    $this->info('Message stats computed successfully.');
    // Log end
    Log::channel('sms_stats')->info("Completed stats computation at " . now());
}



      /**
     * Determine the network from the msisdn prefix.
     */
    private function resolveNetwork($number)
    {
        $number = preg_replace('/^234/', '0', $number);
        $prefix = substr($number, 0, 4);

        return match ($prefix) {
            '0802', '0808', '0812', '0708', '0901', '0902', '0904', '0907', '0912', '0911' => 'Airtel',
            '0805', '0807', '0811', '0815', '0905', '0915' => 'Glo',
            '0809', '0817', '0818', '0908', '0909' => '9mobile',
            default => 'MTN',
        };
    }
}
