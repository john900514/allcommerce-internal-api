<?php

namespace App\Projectors\Sales;

use App\BillingAddresses;
use App\Emails;
use App\Events\Addresses\BillingCreated;
use App\Events\Addresses\BillingUpdated;
use App\Events\Addresses\ShippingCreated;
use App\Events\Addresses\ShippingUpdated;
use App\Events\Leads\CustomerOptedIntoCommunication;
use App\Events\Leads\EmailToAddressLinkBecameAvailable;
use App\Events\Leads\LeadCreated;
use App\Events\Leads\EmailCreated;
use App\Events\Leads\EmailUpdated;
use App\Jobs\Leads\LinkEmailToBillShip;
use App\Events\Shopify\ShopifyCustomerUpdated;
use App\Events\Shopify\ShopifyCustomerCreated;
use App\Jobs\Shopify\Customers\CreateShopifyCustomer;
use App\Jobs\Shopify\Customers\UpdateShopifyCustomer;
use App\LeadAttributes;
use App\ShippingAddresses;
use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

class LeadsProjector extends Projector implements ShouldQueue
{
    public function onLeadCreated(LeadCreated $event)
    {
        $lead = $event->getLead();

        // @todo - do stuff here.
    }

    public function onShippingCreated(ShippingCreated $event)
    {
        $lead = $event->getLead();
        $shipping = $event->getShipping();

        $lead->shipping_uuid = $shipping->id;
        $lead->save();

        $shipping->lead_uuid = $lead->id;
        $shipping->save();

    }

    public function onBillingCreated(BillingCreated $event)
    {
        $lead = $event->getLead();
        $billing = $event->getBilling();

        $lead->billing_uuid = $billing->id;
        $lead->save();

        $billing->lead_uuid = $lead->id;
        $billing->save();
    }

    public function onEmailCreated(EmailCreated $event)
    {
        $lead = $event->getLead();
        $lead->email = $event->getEmail();
        $email = new Emails();
        $email->email = $event->getEmail();
        $email->shop_uuid = $lead->shop_uuid;
        $email->merchant_uuid = $lead->merchant_uuid;
        $email->client_uuid = $lead->client_uuid;

        $email->save();
        $lead->save();
    }

    public function onEmailUpdated(EmailUpdated $event)
    {
        $model = $event->getEmails();
        $model->email = $event->getEmail();
        $model->save();

        $lead = $event->getLead();
        // Update the lead itself;
        $lead->email = $event->getEmail();
        $lead->save();
    }

    public function onShippingUpdated(ShippingUpdated $event)
    {
        $shipping = ShippingAddresses::find($event->getShippingUuid());

        if(!is_null($shipping))
        {
            // Check all applicable fields, skip all non-changes,
            $something_changed = false;
            foreach ($event->getPayload() as $col => $val)
            {
                if($shipping->$col != $val)
                {
                    $shipping->$col = $val;
                    $something_changed = true;
                }
            }

            if($something_changed)
            {
                $shipping->save();
            }
        }
    }

    public function onBillingUpdated(BillingUpdated $event)
    {
        $billing = BillingAddresses::find($event->getBillingUuid());

        if(!is_null($billing))
        {
            // Check all applicable fields, skip all non-changes,
            $something_changed = false;
            foreach ($event->getPayload() as $col => $val)
            {
                if($billing->$col != $val)
                {
                    $billing->$col = $val;
                    $something_changed = true;
                }
            }

            if($something_changed)
            {
                $billing->save();
            }
        }
    }

    public function onShopifyCustomerCreated(ShopifyCustomerCreated $event)
    {
        if(!is_null($attr = $event->getLead()->attributes()->first()))
        {
            $customer_attr = new $attr;
        }

        if(!is_null($customer_attr))
        {
            $customer_attr->lead_uuid = $event->getLead()->id;
            $customer_attr->name = 'shopifyCustomer';
            $customer_attr->value = $event->getDetails()['customer']['id'];
            $customer_attr->misc = $event->getDetails()['customer'];
            $customer_attr->active = 1;
            $customer_attr->shop_uuid = $event->getLead()->shop_uuid;
            $customer_attr->merchant_uuid = $event->getLead()->merchant_uuid;
            $customer_attr->client_uuid =$event->getLead()->client_uuid;
            $customer_attr->save();
        }


        //CreateShopifyCustomer::dispatch($event->getShipping(), $event->getBilling(), $event->getLead())->onQueue("aco-".env('APP_URL')."-shopify");

        /*
        if(!is_null($event->getLead()->email))
        {
            LinkEmailToBillShip::dispatch($event->getShipping(), $event->getBilling(), $event->getLead())->onQueue('aco-'.env('APP_ENV').'-emails');
        }
        */
    }

    public function onShopifyCustomerUpdated(ShopifyCustomerUpdated $event)
    {
        UpdateShopifyCustomer::dispatch($event->getShipping(), $event->getBilling(), $event->getLead())->onQueue("aco-".env('APP_URL')."-shopify");

        if(!is_null($event->getLead()->email))
        {
            LinkEmailToBillShip::dispatch($event->getShipping(), $event->getBilling(), $event->getLead())->onQueue('aco-'.env('APP_ENV').'-emails');
        }
    }

    public function onCustomerOptedIntoCommunication(CustomerOptedIntoCommunication $event)
    {
        $email_attr = $event->getLead()->attributes()
            ->whereName('emailList')
            ->whereActive(1)
            ->first();

        if(!is_null($email_attr))
        {
            if($email_attr->value != $event->getOptin())
            {
                $email_attr->value = $event->getOptin();
                $email_attr->active = 1;
            }
        }
        else
        {
            $lead = $event->getLead();
            $email_attr = new LeadAttributes();
            $email_attr->lead_uuid = $lead->id;
            $email_attr->name = 'emailList';
            $email_attr->value = $event->getOptin();
            $email_attr->misc = [];
            $email_attr->active = 1;

            $email_attr->shop_uuid = $lead->shop_uuid;
            $email_attr->merchant_uuid = $lead->merchant_uuid;
            $email_attr->client_uuid = $lead->client_uuid;
        }

        $email_attr->save();
    }

    public function onEmailToAddressLinkBecameAvailable(EmailToAddressLinkBecameAvailable $event)
    {
        $email = $event->getEmail();
        $shipping = $event->getShippingAddress();

        $billing = $event->getBillingAddress();

        if((!is_null($shipping)))
        {
            $class = $shipping['class'];
            $id = $shipping['id'];
            $shipping = $class::find($id);

            if((!is_null($shipping)))
            {
                $shipping->email = $email;
                $shipping->save();
            }
        }

        if((!is_null($billing)))
        {
            $class = $billing['class'];
            $id = $billing['id'];
            $billing = $class::find($id);

            if((!is_null($shipping)))
            {
                $billing->email = $email;
                $billing->save();
            }
        }
    }
}
