<?php

namespace App\Events\Leads;

use App\BillingAddresses;
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

class EmailToAddressLinkBecameAvailable extends ShouldBeStored
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $email, $shipping, $billing;
    /**
     * Create a new event instance.
     * @param  $email
     * @param  $shipping
     * @param  $billing
     * @return void
     */
    public function __construct(string $email, $shipping = null, $billing = null)
    {
        $this->email = $email;
        $this->shipping = $shipping;
        $this->billing = $billing;
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

    public function getEmail()
    {
        return $this->email;
    }

    public function getShippingAddress()
    {
        return $this->shipping;
    }

    public function getBillingAddress()
    {
        return $this->billing;
    }
}
