<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class QueueListUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $counter_name;
    public $queue_number;
    public $called_at;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($counter_name, $queue_number, $called_at)
    {
        $this->counter_name = $counter_name;
        $this->queue_number = $queue_number;
        $this->called_at = $called_at;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel
     */
    public function broadcastOn()
    {
        return new Channel('queue.list');
    }

    /**
     * Optional: Customize the event name broadcasted
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'QueueListUpdated';
    }

    /**
     * Optional: Specify the payload that will be sent to listeners
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'counter_name' => $this->counter_name,
            'queue_number' => $this->queue_number,
            'called_at' => $this->called_at,
        ];
    }
}
