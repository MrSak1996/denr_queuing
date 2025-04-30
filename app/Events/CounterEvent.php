<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

class CounterEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $service_counter_id;
    public $queue_number;
    public $status;

    public function __construct($service_counter_id, $queue_number,$status)
    {
        $this->service_counter_id = $service_counter_id;
        $this->queue_number = $queue_number;
        $this->status = $status;
    }
    
    public function broadcastOn()
    {
        return new Channel("client.counter.{$this->service_counter_id}");
    }

    public function broadcastAs()
    {
        return 'CounterEvent';
    }
}
