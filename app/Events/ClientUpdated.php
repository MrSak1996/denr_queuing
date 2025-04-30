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

    public function __construct(QueuesModel $queue)
    {
        $this->client = [
            'counter_name' => $queue->counter_name ?? null,
            'queue_id' => $queue->id ?? null,
            'counter_id' => $queue->counter_id ?? null,
            'queue_number' => $queue->queue_number ?? null,
            'status' => $queue->status ?? null,
            'queued_at' => $queue->queued_at ?? null,
        ];
    }
    

    public function broadcastOn()
    {
        return new Channel('clients.counter.' . ($this->client['counter_id'] ?? 'default'));
    }

    public function broadcastWith()
    {
        return ['client' => $this->client];
    }
}

