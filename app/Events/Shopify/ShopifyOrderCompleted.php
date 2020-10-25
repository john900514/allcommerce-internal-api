<?php

namespace App\Events\Shopify;

use App\Leads;
use App\Models\Sales\Orders;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class ShopifyOrderCompleted extends ShouldBeStored
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $order, $transaction, $closed;

    /**
     * Create a new event instance.
     * @param Orders $order
     * @param array $transaction
     * @param array $closed
     * @return void
     */
    public function __construct(Orders $order, array $transaction)
    {
        $this->order = $order;
        $this->transaction = $transaction;
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

    public function getTransaction()
    {
        return $this->transaction;
    }
}
