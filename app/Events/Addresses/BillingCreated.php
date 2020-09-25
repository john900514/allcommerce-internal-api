<?php

namespace App\Events\Addresses;

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

class BillingCreated extends ShouldBeStored
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $billing, $lead;
    /**
     * Create a new event instance.
     * @param Leads $lead
     * @param BillingAddresses $billing
     * @return void
     */
    public function __construct(BillingAddresses $billing, Leads $lead)
    {
        $this->lead = $lead;
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

    public function getLead()
    {
        return $this->lead;
    }

    public function getBilling()
    {
        return $this->billing;
    }
}


