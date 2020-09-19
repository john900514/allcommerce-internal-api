<?php

namespace App\Aggregates\Orders;

use App\BillingAddresses;
use App\Events\Leads\EmailCreated;
use App\Events\Leads\EmailUpdated;
use App\Events\Leads\LeadCreated;
use App\Events\Shopify\ShopifyCustomerCreated;
use App\Events\Shopify\ShopifyCustomerUpdated;
use App\Leads;
use App\ShippingAddresses;
use Illuminate\Support\Facades\Validator;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;

class ShopifyOrderAggregate extends AggregateRoot
{
    protected static bool $allowConcurrency = true;

    private $line_items = [];
    private $lead_record;
    private $email_address;
    private $shipping_address;
    private $billing_address;
    private $shopify_customer, $shopify_draft_order, $shopify_order;

    public function addLeadRecord(Leads $lead, $record = true)
    {
        $this->lead_record = $lead;

        if($record) {
            $this->recordThat(new LeadCreated($lead));
            $this->persist();
        }

        return $this;
    }

    public function addLineItems(array $products)
    {
        // curate this.
        if(count($products) > 0)
        {
            foreach ($products as $idx => $product)
            {
                if(array_key_exists($idx, $this->line_items))
                {
                    $diff = array_diff_assoc($this->line_items[$idx], $product);
                }
                else
                {
                    $this->line_items[$idx] = $product;
                }

            }

        }

        return $this;
    }

    public function addEmailAddress($email)
    {
        $this->email_address = $email;

        if(!is_null($this->lead_record))
        {
            $email_record = $this->lead_record->email_record()->first();

            if(is_null($email_record))
            {
                $this->recordThat(new EmailCreated($email, $this->lead_record));
                $this->persist();
            }
            elseif($email_record->email != $email)
            {
                if($email_record->shop_uuid == $this->lead_record->shop_uuid)
                {
                    $this->recordThat(new EmailUpdated($email, $email_record));
                    $this->persist();
                }
            }

        }

        return $this;
    }

    public function addBillingAddress(BillingAddresses $addy)
    {
        $this->billing_address = $addy;

        return $this;
    }

    public function addShippingAddress(ShippingAddresses $addy)
    {
        $this->shipping_address = $addy;

        if((!is_null($this->billing_address)) && (!is_null($this->lead_record)))
        {
            // check the lead_attributes for a shopify_customer
            $customer_attr = $this->lead_record->attributes()
                ->whereName('shopifyCustomer')
                ->first();

            if(is_null($customer_attr))
            {
                $this->recordThat(new ShopifyCustomerCreated($this->shipping_address, $this->billing_address, $this->lead_record));
                $this->persist();
            }
            else
            {
                $this->recordThat(new ShopifyCustomerUpdated($this->shipping_address, $this->billing_address, $this->lead_record));
                $this->persist();
            }

            $customer_attr = $this->lead_record->attributes()
                ->whereName('shopifyCustomer')
                ->first();

            if(!is_null($customer_attr))
            {
                $this->shopify_customer = $customer_attr->misc;
            }
        }

        return $this;
    }

    public function createOrUpdateDraftOrder()
    {
        /**
         * MUST NOT BE NULL
         * 1. $line_items,
         * 2. $lead_record,
         * 3. $billing_phone && $shipping_phone
         * 4. $email_address, $billing_address, shipping_address
         * 5. $shopify_customer
         */
        $req_map = [
            'line_items' => $this->line_items,
            'lead_record' => $this->lead_record,
            'email_address' => $this->email_address,
            'billing_address' => $this->billing_address,
            'shipping_address' => $this->shipping_address,
            'shopify_customer' => $this->shopify_customer
        ];

        $validated = Validator::make($req_map, [
            'line_items' => 'required|array', // not empty
            'lead_record' => 'required', // Is a lead model
            'billing_phone' => 'required', // is a phone number
            'shipping_phone' => 'required', // is a phone number
            'email_address' => 'required', // is an email address
            'billing_address' => 'required', // is a BillingAddresses Model
            'shipping_address' => 'required', //is a Shipping Addresses model
            'shopify_customer' => 'required', // is a lead attributes value
        ]);

        if($failed = $validated->fails())
        {
            // Nothing, it's not ready.
        }
        else
        {
            // Run the LeadQualifiedForDraftOrder event
        }

        return $this;
    }
}
