<?php

namespace App\Projectors\Sales;

use App\Emails;
use App\Events\Leads\LeadCreated;
use App\Events\Leads\EmailCreated;
use App\Events\Leads\EmailUpdated;
use App\Jobs\Leads\LinkEmailToBillShip;
use App\Events\Shopify\ShopifyCustomerUpdated;
use App\Events\Shopify\ShopifyCustomerCreated;
use App\Jobs\Shopify\Customers\CreateShopifyCustomer;
use App\Jobs\Shopify\Customers\UpdateShopifyCustomer;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

class LeadsProjector extends Projector
{
    public function onLeadCreated(LeadCreated $event)
    {
        $lead = $event->getLead();

        // @todo - do stuff here.
    }

    public function onEmailCreated(EmailCreated $event)
    {
        $lead = $event->getLead();
        $email = new Emails();
        $email->email = $event->getEmail();
        $email->shop_uuid = $lead->shop_uuid;
        $email->merchant_uuid = $lead->merchant_uuid;
        $email->client_uuid = $lead->client_uuid;

        $email->save();
    }

    public function onEmailUpdated(EmailUpdated $event)
    {
        $model = $event->getEmailModel();
        $model->email = $event->getEmailAddress();
        $model->save();
    }

    public function onShopifyCustomerCreated(ShopifyCustomerCreated $event)
    {
        CreateShopifyCustomer::dispatch($event->getShipping(), $event->getBilling(), $event->getLead())->onQueue("aco-".env('APP_URL')."-shopify");

        if(!is_null($event->getLead()->email))
        {
            LinkEmailToBillShip::dispatch($event->getShipping(), $event->getBilling(), $event->getLead())->onQueue('aco-'.env('APP_ENV').'-emails');
        }
    }

    public function onShopifyCustomerUpdated(ShopifyCustomerUpdated $event)
    {
        UpdateShopifyCustomer::dispatch($event->getShipping(), $event->getBilling(), $event->getLead())->onQueue("aco-".env('APP_URL')."-shopify");

        if(!is_null($event->getLead()->email))
        {
            LinkEmailToBillShip::dispatch($event->getShipping(), $event->getBilling(), $event->getLead())->onQueue('aco-'.env('APP_ENV').'-emails');
        }

    }
}
