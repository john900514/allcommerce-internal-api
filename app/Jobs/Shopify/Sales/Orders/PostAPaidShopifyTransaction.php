<?php

namespace App\Jobs\Shopify\Sales\Orders;

use App\Aggregates\Orders\ShopifyOrderAggregate;
use App\Models\Sales\Orders;
use App\Models\Sales\Transactions;
use App\Services\Shopify\ShopifyOrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PostAPaidShopifyTransaction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order, $transaction;
    /**
     * Create a new event instance.
     * @param Orders $order
     * @param Transactions $transaction
     * @return void
     */
    public function __construct(Orders $order, Transactions $transaction)
    {
        $this->order = $order;
        $this->transaction = $transaction;
    }

    public function handle(ShopifyOrderService $order_svc)
    {
        // Get the authorization transaction platform_transaction_id
        $auth_transaction = $this->order->attributes()
            ->where('misc->charge_type', '=', 'auth')
            ->first();

        if(!is_null($auth_transaction))
        {
            $shopifyOrder = $this->order->attributes()->whereName('shopifyOrder')->first();
            // Get the final price from the passed in $this->transaction
            $final_price = $this->transaction->total;

            $shop_install = $this->order->shop_install()->first();

            // // Use the Shopify Order Service to post the "paid" transaction
            $response = $order_svc->postPaidTransaction(
                $shop_install,
                $final_price,
                $shopifyOrder->misc['id']
            );


            if(array_key_exists('transaction', $response))
            {
                $this->transaction->platform_transaction_id = $response['transaction']['id'];
                $this->transaction->save();

//              // ping Shopify to close the order (no, this archives the order, so lets not do that)
                // keep the reference here, tho
                //$closed_order  = $order_svc->closeOrder($shop_install, $shopifyOrder->misc['id']);

                if(true) //if(array_key_exists('order', $closed_order))
                {
                    // retrieve the ShopifyOrderAggregate and tell it to close the order
                    //    it will fire a job that sets the order record with a misc closed.
                    ShopifyOrderAggregate::retrieve($this->order->lead_uuid)
                        ->setOrderComplete($response['transaction'])
                        ;//->persist();
                }
            }

        }

    }
}
