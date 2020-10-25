<?php

namespace App\Projectors\Sales;

use App\Events\Customers\CustomerPurchasedOrderLinked;
use App\Events\Orders\OrderPaymentAuthorized;
use App\Events\Orders\OrderPaymentCaptured;
use App\Events\Shopify\ShopifyDraftOrderCreated;
use App\Events\Shopify\ShopifyDraftOrderUpdated;
use App\Events\Shopify\ShopifyOrderCompleted;
use App\Jobs\Shopify\Sales\Orders\CreateShopifyOrder;
use App\Jobs\Shopify\Sales\Orders\PostAPaidShopifyTransaction;
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
        CreateShopifyOrder::dispatch($event->getTransaction()->order()->first())
            ->onQueue('aco-'.env('APP_ENV').'-shopify');
    }

    public function onOrderPaymentCaptured(OrderPaymentCaptured $event)
    {
        // Determine the platform of the order.
        $platform = $event->getTransaction()->misc['platform'];

        switch($platform)
        {
            case 'Shopify':
                // fire job that pings Shopify to post the "paid" transaction
                $order = $event->getTransaction()->order()->first();
                PostAPaidShopifyTransaction::dispatch($order, $event->getTransaction())->onQueue('aco-'.env('APP_ENV').'-shopify');
                break;
        }
    }

    public function onCustomerPurchasedOrderLinked(CustomerPurchasedOrderLinked $event)
    {
        // Add the Customer record to the order attributes
        $attr = new OrderAttributes();
        $attr->order_uuid = $event->getOrder()->id;
        $attr->name = 'customer';
        $attr->value = $event->getCustomer()->id;
        $attr->misc = [];
        $attr->active = 1;
        $attr->shop_uuid = $event->getOrder()->shop_uuid;
        $attr->merchant_uuid = $event->getOrder()->merchant_uuid;
        $attr->client_uuid = $event->getOrder()->client_uuid;
        $attr->save();
    }

    public function onShopifyOrderCompleted(ShopifyOrderCompleted $event)
    {
        // Get the order
        $order = $event->getOrder();

        // Spark up a new orders_attributes instance
        $attr_model = new OrderAttributes();
        $attr_model->order_uuid = $order->id;
        $attr_model->active = 1;
        $attr_model->shop_uuid = $order->shop_uuid;
        $attr_model->merchant_uuid = $order->merchant_uuid;
        $attr_model->client_uuid = $order->client_uuid;

        // Make a new record for the shopifyCaptureTransaction
        $trans_attr = $attr_model;
        $trans_attr->name = 'shopifyCaptureTransaction';
        $trans_attr->value = $event->getTransaction()['id'];
        $trans_attr->misc = $event->getTransaction();
        $trans_attr->save();

        // Make a new record the closedShopifyOrder
        $closed_attr = $attr_model;
        $closed_attr->name = 'closedShopifyOrder';
        $closed_attr->value = 'closed';
        $closed_attr->misc = [];//$event->getClosed();
        $closed_attr->save();

        // Update the order record's misc to have closed = true
        $order->misc = ['closed' => true];
        $order->save();
    }
}
