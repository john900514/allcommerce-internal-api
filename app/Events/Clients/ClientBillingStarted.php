<?php

namespace App\Events\Clients;

use App\BillingAddresses;
use App\Clients;
use App\Leads;
use App\ShippingAddresses;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ClientBillingStarted extends ShouldBeStored
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $client;
    /**
     * Create a new event instance.
     * @param Clients $client
     * @return void
     */
    public function __construct(Clients $client)
    {
        $this->client = $client;
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

    public function getClient()
    {
        return $this->client;
    }
}


