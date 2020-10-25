<?php

namespace App\Events\Customers;

use App\Emails;
use App\Models\Customers\Customer;
use App\Models\Sales\Orders;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class CustomerPurchasedOrderLinked extends ShouldBeStored
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $customer, $order;

    /**
     * Create a new event instance.
     * @param Customer $customer
     * @param Orders $order
     * @return void
     */
    public function __construct(Customer $customer, Orders $order)
    {
        $this->customer = $customer;
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

    public function getCustomer()
    {
        return $this->customer;
    }

    public function getOrder()
    {
        return $this->order;
    }
}
