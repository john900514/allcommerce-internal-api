<?php

namespace App\Projectors\Sales;

use App\Events\Shopify\ShopifyDraftOrderCreated;
use App\Events\Shopify\ShopifyDraftOrderUpdated;
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
}
