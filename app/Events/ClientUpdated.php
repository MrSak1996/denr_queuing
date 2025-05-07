<?php

namespace App\Events;

use App\Models\QueuesModel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClientUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $client;
    public $old_counter_id; 

    public function __construct(QueuesModel $queue, $oldCounterId = null)
    {
        $this->old_counter_id = $oldCounterId;

        $this->client = [
            'counter_name' => $queue->counter_name ?? 'Counter ' . $queue->counter_id,
            'queue_id' => $queue->id ?? null,
            'counter_id' => $queue->counter_id ?? null,
            'queue_number' => $queue->queue_number ?? null,
            'status' => $queue->status ?? null,
            'queued_at' => $queue->queued_at ?? null,
            'priority_level_id' => $queue->priority_level_id ?? null,
            'priority_level' => ($queue->priority_level_id == 1) ? 'PWD' 
                                : (($queue->priority_level_id == 2) ? 'Senior'
                                : (($queue->priority_level_id == 3) ? 'Pregnant'
                                : 'Regular')),
        ];
        
        
    }
    

    public function broadcastOn()
{
    $channels = [];

    // Always broadcast to new counter
    $channels[] = new Channel('clients.counter.' . ($this->client['counter_id'] ?? 'default'));

    // If there’s an old counter, broadcast there too
    if (!is_null($this->old_counter_id)) {
        $channels[] = new Channel('clients.counter.' . $this->old_counter_id);
    }

    return $channels;
}


    public function broadcastWith()
    {
        return ['client' => $this->client];
    }
}

