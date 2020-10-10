<?php

namespace App\Events\Leads;

use App\Leads;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class LineItemsAdded extends ShouldBeStored
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $lead, $products;
    /**
     * Create a new event instance.
     * @param Leads $lead
     * @param array $products
     * @return void
     */
    public function __construct(Leads $lead, array $products)
    {
        $this->lead = $lead;
        $this->products = $products;
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

    public function getProducts()
    {
        return $this->products;
    }


}


