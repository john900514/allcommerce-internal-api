<?php

namespace App\Events\Leads;

use App\Emails;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class EmailUpdated extends ShouldBeStored
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $email, $model;
    /**
     * Create a new event instance.
     * @param string $email
     * @param Emails $model
     * @return void
     */
    public function __construct(string $email, Emails $model)
    {
        $this->email = $email;
        $this->model = $model;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }

    public function getEmailAddress()
    {
        return $this->email;
    }

    public function getEmailModel()
    {
        return $this->model;
    }
}
