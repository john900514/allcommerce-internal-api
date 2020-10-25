<?php

namespace App\Events\Customers;

use App\Emails;
use App\Models\Customers\Customer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class CustomerEmailLinked extends ShouldBeStored
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $customer, $email;

    /**
     * Create a new event instance.
     * @param Customer $customer
     * @param Emails $email
     * @return void
     */
    public function __construct(Customer $customer, Emails $email)
    {
        $this->customer = $customer;
        $this->email = $email;
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

    public function getEmail()
    {
        return $this->email;
    }
}
