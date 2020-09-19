<?php

namespace App\Events\Shopify;

use App\Leads;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ShopifyDraftOrderCreated extends ShouldBeStored
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $lead, $checkout_details;

    /**
     * Create a new event instance.
     * @param Leads $lead
     * @param array $checkout_details
     * @return void
     */
    public function __construct(Leads $lead, array $checkout_details)
    {
        $this->lead = $lead;
        $this->checkout_details = $checkout_details;
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

    public function getCheckoutDetails()
    {
        return $this->checkout_details;
    }
}
