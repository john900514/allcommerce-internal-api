<?php

namespace App\Events\Orders;

use App\Leads;
use App\Models\Sales\Orders;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class LeadConvertedToOrder extends ShouldBeStored
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $order, $lead;

    /**
     * Create a new event instance.
     * @param Orders $order
     * @param Leads $lead
     * @return void
     */
    public function __construct(Orders $order, Leads $lead)
    {
        $this->order = $order;
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

    public function getOrder()
    {
        return $this->order;
    }

    public function getLead()
    {
        return $this->lead;
    }
}
