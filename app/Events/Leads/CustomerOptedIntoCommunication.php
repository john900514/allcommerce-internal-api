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

class CustomerOptedIntoCommunication extends ShouldBeStored
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $lead, $optin;
    /**
     * Create a new event instance.
     * @param Leads $lead
     * @param bool $optin
     * @return void
     */
    public function __construct(Leads $lead, bool $optin)
    {
        $this->lead = $lead;
        $this->optin = $optin;
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

    public function getLead()
    {
        return $this->lead;
    }

    public function getOptin()
    {
        return $this->optin;
    }
}
