<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class NotificationEvent implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $notification;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('notifications.' . $this->notification->user_id);
    }

    /**
     * Define the payload for the broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'notification' => $this->notification,
        ];
    }

    /**
     * Optional: Set a custom broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'NotificationEvent';
    }
}
