<?php

// app/Events/ClientTransferred.php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class ClientTransferred implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $queue_number, $old_counter_id, $old_counter_name, $new_counter_id, $new_counter_name, $is_transfer;

    public function __construct($queue_number, $old_counter_id, $old_counter_name, $new_counter_id, $new_counter_name)
    {
        $this->queue_number = $queue_number;
        $this->old_counter_id = $old_counter_id;
        $this->old_counter_name = $old_counter_name;
        $this->new_counter_id = $new_counter_id;
        $this->new_counter_name = $new_counter_name;
        $this->is_transfer = true;

    }

    public function broadcastOn()
    {
        return new Channel('admin.serving');
    }

    public function broadcastAs()
    {
        return 'ClientTransferred';
    }
}
