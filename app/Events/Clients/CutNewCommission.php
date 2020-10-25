<?php

namespace App\Events\Clients;

use App\BillingAddresses;
use App\Clients;
use App\Leads;
use App\Models\Sales\Transactions;
use App\ShippingAddresses;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class CutNewCommission extends ShouldBeStored
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $transaction;

    /**
     * Create a new event instance.
     * @param Transactions $transaction
     * @return void
     */
    public function __construct(Transactions $transaction)
    {
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

    public function getTransaction()
    {
        return $this->transaction;
    }
}


