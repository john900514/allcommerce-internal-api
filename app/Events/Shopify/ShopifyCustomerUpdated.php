<?php

namespace App\Events\Shopify;

use App\Leads;
use App\BillingAddresses;
use App\ShippingAddresses;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ShopifyCustomerUpdated extends ShouldBeStored
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $shipping, $billing, $lead;
    /**
     * Create a new event instance.
     * @param ShippingAddresses $shipping
     * @param BillingAddresses $billing
     * @param Leads $lead
     * @return void
     */
    public function __construct(ShippingAddresses $shipping, BillingAddresses $billing, Leads $lead)
    {
        $this->shipping = $shipping;
        $this->billing = $billing;
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

    public function getShipping()
    {
        return $this->shipping;
    }

    public function getBilling()
    {
        return $this->billing;
    }

    public function getLead()
    {
        return $this->lead;
    }
}
