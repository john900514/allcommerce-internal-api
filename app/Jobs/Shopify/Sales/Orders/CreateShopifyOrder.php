<?php

namespace App\Jobs\Shopify\Sales\Orders;

use App\Aggregates\Orders\ShopifyOrderAggregate;
use App\BillingAddresses;
use App\InventoryVariants;
use App\Leads;
use App\LeadAttributes;
use App\Models\Sales\OrderAttributes;
use App\Models\Sales\Orders;
use App\Models\Sales\Transactions;
use App\Services\Shopify\ShopifyDraftOrderService;
use App\Services\Shopify\ShopifyOrderService;
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

    public function handle(ShopifyDraftOrderService $service,
                           ShopifyOrderService $order_svc,
                           Transactions $ac_transactions,
                           OrderAttributes $attributes)
    {
        // Get the Lead and the draftorder attribute
        $aggy = ShopifyOrderAggregate::retrieve($this->order->lead_uuid);
        $draftOrder = $aggy->getShopifyDraftOrder();
        $shop_install = $this->order->shop_install()->first();

        // check the order attributes first to see if there is an order
        // this should only happen in development mode.
        $saved_order = $this->order->attributes()->whereName('shopifyOrder')->first();
        if(!is_null($saved_order))
        {
            $response = ['draft_order' => [
                'order_id' => $saved_order->value
            ]];
        }
        else
        {
            // Use the service to ping shopify to convert the draft order
            $response = $service->completeDraftOrder($shop_install, $draftOrder['id']);
        }

        if(array_key_exists('draft_order', $response))
        {
            // Pull the order id from the response.
            $shopify_order_id = $response['draft_order']['order_id'];
            // Get the Order from Shopify.
            $shopify_order = $order_svc->getOrder($shop_install, $shopify_order_id);

            if(array_key_exists('order', $shopify_order))
            {
                // Get the generated transaction and hang on to the id.
                $transactions = $order_svc->getTransactions($shop_install, $shopify_order_id);

                if(count($transactions['transactions']) > 0)
                {
                    $transaction = $transactions['transactions'][0];

                    $saved_order = $this->order->attributes()->whereName('transaction')->first();
                    $trans_record = $ac_transactions->find($saved_order->value);
                    $trans_record->platform_transaction_id = $transaction['id'];
                    $trans_record->save();

                    // Cut an order attribute record.
                    $attr = new $attributes();
                    $attr->order_uuid = $this->order->id;
                    $attr->name = 'shopifyOrder';
                    $attr->value = $shopify_order['order']['id'];
                    $attr->misc = $shopify_order['order'];
                    $attr->active = 1;
                    $attr->shop_uuid = $this->order->shop_uuid;
                    $attr->merchant_uuid = $this->order->merchant_uuid;
                    $attr->client_uuid = $this->order->client_uuid;
                    $attr->save();

                    // have $aggy log the shopify order along with it's transaction id
                    $aggy->setShopifyOrder($shopify_order['order'])
                        ->persist();
                }
            }
        }
    }
}
