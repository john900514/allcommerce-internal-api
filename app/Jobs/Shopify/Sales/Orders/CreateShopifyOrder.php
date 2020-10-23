<?php

namespace App\Jobs\Shopify\Sales\Orders;

use App\BillingAddresses;
use App\InventoryVariants;
use App\Leads;
use App\LeadAttributes;
use App\Models\Sales\OrderAttributes;
use App\Models\Sales\Orders;
use App\ShippingAddresses;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\Shopify\ShopifyAdminAPIService;

class CreateShopifyOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;

    /**
     * Create a new event instance.
     * @param Orders $order
     * @return void
     */
    public function __construct(Orders $order)
    {
        $this->order = $order;
    }

    public function handle(ShopifyAdminAPIService $service, OrderAttributes $attributes)
    {
        /**
         * STEPS
         * 1. Get the Lead and the draftorder attribute
         * 2. Use the service to ping shopify to convert the draft order
         * 3. If successful, call $aggy - the Shopify Order aggregator
         * 4. have $aggy log the shopify order
         * 5. Cut an order attribute record.
         * 5. Use the service to log the auth transaction with Shopify
         */
    }
}
