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

class ShopifyCustomerCreated extends ShouldBeStored
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $details, $lead;
    /**
     * Create a new event instance.
     * @param array $details
     * @param Leads $lead
     * @return void
     */
    public function __construct(array $details, Leads $lead)
    {
        $this->details = $details;
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

    public function getDetails()
    {
        return $this->details;
    }

    public function getLead()
    {
        return $this->lead;
    }
}
