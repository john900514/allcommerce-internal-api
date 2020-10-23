<?php

namespace App\Projectors\Sales;

use App\Events\Orders\OrderPaymentAuthorized;
use App\Events\Shopify\ShopifyDraftOrderCreated;
use App\Events\Shopify\ShopifyDraftOrderUpdated;
use App\Jobs\Shopify\Sales\Orders\CreateShopifyOrder;
use App\Models\Sales\OrderAttributes;
use App\Models\Sales\Orders;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use App\Jobs\Shopify\Sales\DraftOrders\CreateShopifyDraftOrder;
use App\Jobs\Shopify\Sales\DraftOrders\UpdateShopifyDraftOrder;

class OrdersProjector extends Projector
{
    public function onShopifyDraftOrderCreated(ShopifyDraftOrderCreated $event)
    {
        if(!is_null($attr = $event->getLead()->attributes()->first()))
        {
            $draft_attr = new $attr;
        }

        if(!is_null($draft_attr))
        {
            $draft_order = $event->getCheckoutDetails();
            $draft_attr->lead_uuid = $event->getLead()->id;
            $draft_attr->name = 'shopifyDraftOrder';
            $draft_attr->value = $draft_order['id'];
            $draft_attr->misc = $draft_order;
            $draft_attr->active = 1;
            $draft_attr->shop_uuid = $event->getLead()->shop_uuid;
            $draft_attr->merchant_uuid = $event->getLead()->merchant_uuid;
            $draft_attr->client_uuid = $event->getLead()->client_uuid;
            $draft_attr->save();
        }



        /*
        CreateShopifyDraftOrder::dispatch($event->getLead(), $event->getCheckoutDetails())
            ->onQueue('aco-'.env('APP_ENV').'-shopify');
        */
    }

    public function onShopifyDraftOrderUpdated(ShopifyDraftOrderUpdated $event)
    {
        // If success create lead_attribute record
        $draft_attr = $event->getLead()->attributes()->whereName('shopifyDraftOrder')->first();
        $draft_order = $event->getCheckoutDetails();
        $draft_attr->misc = $draft_order;
        $draft_attr->save();

        /*
        UpdateShopifyDraftOrder::dispatch($event->getLead(), $event->getCheckoutDetails())
            ->onQueue('aco-'.env('APP_ENV').'-shopify');
        */
    }

    public function onOrderPaymentAuthorized(OrderPaymentAuthorized $event)
    {
        // Determine the platform of the order.
        $platform = $event->getTransaction()->misc['platform'];

        switch($platform)
        {
            case 'Shopify':
                // Get the order loaded
                $order = Orders::find($event->getTransaction()->order_uuid);

                $trans_attr = $order->attributes()
                    ->whereName('transaction')
                    ->whereActive(1)
                    ->first();

                if(!is_null($trans_attr))
                {
                    if($trans_attr->value != $event->getTransaction()->id)
                    {
                        $trans_attr->value = $event->getTransaction()->id;
                        $trans_attr->misc = $event->getTransaction()->misc;
                        $trans_attr->active = 1;
                    }
                }
                else
                {
                    $trans_attr = new OrderAttributes();
                    $trans_attr->order_uuid = $order->id;
                    $trans_attr->name = 'transaction';
                    $trans_attr->value = $event->getTransaction()->id;
                    $trans_attr->misc = $event->getTransaction()->misc;
                    $trans_attr->active = 1;

                    $trans_attr->shop_uuid = $order->shop_uuid;
                    $trans_attr->merchant_uuid = $order->merchant_uuid;
                    $trans_attr->client_uuid = $order->client_uuid;
                }

                $trans_attr->save();
                // Fire the createShopifyOrder Job
                CreateShopifyOrder::dispatch($order)->onQueue('aco-'.env('APP_ENV').'-shopify');
                break;
        }
    }
}
