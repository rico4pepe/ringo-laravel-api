<?php

namespace App\Events;
use Illuminate\Broadcasting\InteractsWithSockets;
//use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImportProgressUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $rowCount;
    public $successCount;
    public $failCount;

    public function __construct($rowCount, $successCount, $failCount)
    {
        $this->rowCount = $rowCount;
        $this->successCount = $successCount;
        $this->failCount = $failCount;
    }

    public function broadcastOn()
    {
        return ['import-progress'];
    }

    public function broadcastAs()
    {
        return 'progress.updated';
    }
}
