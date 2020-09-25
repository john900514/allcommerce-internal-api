<?php

namespace App\Events\Addresses;

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

class BillingUpdated extends ShouldBeStored
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $payload, $billing_uuid;
    /**
     * Create a new event instance.
     * @param string $billing_uuid
     * @param array $payload
     * @return void
     */
    public function __construct(string $billing_uuid, array $payload)
    {
        $this->payload = $payload;
        $this->billing_uuid = $billing_uuid;
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

    public function getPayload()
    {
        return $this->payload;
    }

    public function getBillingUuid()
    {
        return $this->billing_uuid;
    }
}


