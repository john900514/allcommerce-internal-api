<?php

namespace App\Events\Orders;

use App\Leads;
use App\Models\Sales\Orders;
use App\Models\Sales\Transactions;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ShopifyOrderCreated extends ShouldBeStored
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $order;

    /**
     * Create a new event instance.
     * @param Orders $order
     * @return void
     */
    public function __construct(array $order)
    {
        $this->order = $order;
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
}
