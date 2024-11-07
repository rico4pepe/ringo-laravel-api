<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImportProgressUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $processedCount;
    public $successCount;
    public $failCount;

    /**
     * Create a new event instance.
     *
     * @param int $processedCount
     * @param int $successCount
     * @param int $failCount
     */
    public function __construct($processedCount, $successCount, $failCount)
    {
        //
        $this->processedCount = $processedCount;
        $this->successCount = $successCount;
        $this->failCount = $failCount;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('import-progress');
    }
}
