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

class ShippingUpdated extends ShouldBeStored
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $payload, $shipping_uuid;
    /**
     * Create a new event instance.
     * @param string $shipping_uuid
     * @param array $payload
     * @return void
     */
    public function __construct(string $shipping_uuid, array $payload)
    {
        $this->payload = $payload;
        $this->shipping_uuid = $shipping_uuid;
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

    public function getShippingUuid()
    {
        return $this->shipping_uuid;
    }
}


