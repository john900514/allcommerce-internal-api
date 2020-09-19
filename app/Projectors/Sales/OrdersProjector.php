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
        CreateShopifyDraftOrder::dispatch($event->getLead(), $event->getCheckoutDetails())
            ->onQueue('aco-'.env('APP_ENV').'-shopify');
    }

    public function onShopifyDraftOrderUpdated(ShopifyDraftOrderUpdated $event)
    {
        UpdateShopifyDraftOrder::dispatch($event->getLead(), $event->getCheckoutDetails())
            ->onQueue('aco-'.env('APP_ENV').'-shopify');
    }
}
