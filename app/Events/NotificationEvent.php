<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotificationEvent implements ShouldBroadcast
{
    use SerializesModels;

    public $notification;

    /**
     * Create a new event instance.
     *
     * @param Notification $notification
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
        $channel = 'notifications.' . $this->notification->user_id;

        return new PrivateChannel($channel);
    }

    /**
     * Datos que se enviarán al frontend.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'id' => $this->notification->id,
            'type' => $this->notification->type,
            'message' => $this->notification->data['message'] ?? '',
            'created_at' => $this->notification->created_at,
        ];
    }
}
