<?php

namespace App\Events\Leads;

use App\Leads;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class EmailCreated extends ShouldBeStored
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $email, $lead;

    /**
     * Create a new event instance.
     * @param string $email
     * @param Leads $lead
     * @return void
     */
    public function __construct(string $email, Leads $lead)
    {
        $this->email = $email;
        $this->lead = $lead;
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

    public function getEmail() : string
    {
        return $this->email;
    }

    public function getLead()
    {
        return $this->lead;
    }
}
