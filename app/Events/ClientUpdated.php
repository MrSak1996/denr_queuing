<?php

namespace App\Events;

use App\Models\Clients;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClientUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $client;

    public function __construct(Clients $client)
    {
    
        $this->client = [
            'queue_id' => $client->queues[0]->id ?? null,
            'counter_id' => $client->queues[0]->counter_id ?? null,
            'queue_number' => $client->queues[0]->queue_number ?? null,
            'status' => $client->queues[0]->status ?? null,
            'client_name' => $client->name, // or other needed fields
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

