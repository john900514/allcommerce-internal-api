<?php

namespace App\Events\Customers;

use App\Models\Customers\Customer;
use App\Models\PaymentGateways\PaymentProviders;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class CustomerRebillTokenAcquired extends ShouldBeStored
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $customer, $gateway, $token;

    /**
     * Create a new event instance.
     * @param Customer $customer
     * @param PaymentProviders $gateway
     * @param array $token
     * @return void
     */
    public function __construct(Customer $customer, PaymentProviders $gateway, array $token)
    {
        $this->customer = $customer;
        $this->gateway = $gateway;
        $this->token = $token;
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

    public function getGateway()
    {
        return $this->gateway;
    }

    public function getToken()
    {
        return $this->token;
    }
}
