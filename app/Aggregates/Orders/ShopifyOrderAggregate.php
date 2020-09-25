<?php

namespace App\Aggregates\Orders;

use App\BillingAddresses;
use App\Events\Addresses\BillingCreated;
use App\Events\Addresses\BillingUpdated;
use App\Events\Addresses\ShippingCreated;
use App\Events\Addresses\ShippingUpdated;
use App\Events\Leads\CustomerOptedIntoCommunication;
use App\Events\Leads\EmailCreated;
use App\Events\Leads\EmailToAddressLinkBecameAvailable;
use App\Events\Leads\EmailUpdated;
use App\Events\Leads\LeadCreated;
use App\Events\Shopify\ShopifyCustomerCreated;
use App\Events\Shopify\ShopifyCustomerUpdated;
use App\Events\Shopify\ShopifyDraftOrderCreated;
use App\Events\Shopify\ShopifyDraftOrderUpdated;
use App\LeadAttributes;
use App\Leads;
use App\ShippingAddresses;
use Illuminate\Support\Facades\Validator;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;

class ShopifyOrderAggregate extends AggregateRoot
{
    protected static bool $allowConcurrency = true;

    private $line_items = [];
    private $lead_record;
    private $email_address, $contact_optin;
    private $lead_attributes = [];
    private $shipping_address;
    private $billing_address;
    private $shopify_customer, $shopify_draft_order, $shopify_order;

    public function addLeadRecord(Leads $lead, $record = true)
    {
        $this->lead_record = $lead;

        if($record) {
            $this->recordThat(new LeadCreated($lead));
            //$this->persist();
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
            $email_record = $this->lead_record->email_record()
                ->whereShopUuid($this->lead_record->shop_uuid)
                ->first();

            if(is_null($email_record))
            {
                $this->recordThat(new EmailCreated($email, $this->lead_record));
            }
            elseif($email_record->email != $email)
            {
                if($email_record->shop_uuid == $this->lead_record->shop_uuid)
                {
                    $this->recordThat(new EmailUpdated($email, $email_record));
                }
            }

        }

        return $this;
    }

    public function addContactOptin(bool $optin) : self
    {
        $this->contact_optin = $optin;

        $email_attr = $this->lead_record->attributes()
            ->whereName('emailList')
            ->whereActive(1)
            ->first();

        if(!is_null($email_attr))
        {
            if($email_attr->value != $optin)
            {
                $this->recordThat(new CustomerOptedIntoCommunication($this->lead_record, $optin));
            }

            // Don't record anything if there was no change.
        }
        else
        {
            $this->recordThat(new CustomerOptedIntoCommunication($this->lead_record, $optin));
        }

        return $this;
    }

    public function addShippingAddress(ShippingAddresses $shipping, $record = true)
    {
        $this->shipping_address = $shipping;

        if($record) {
            $this->recordThat(new ShippingCreated($shipping, $this->lead_record));
        }

        return $this;
    }

    public function addBillingAddress(BillingAddresses $billing, $record = true)
    {
        $this->billing_address = $billing;

        if($record) {
            $this->recordThat(new BillingCreated($billing, $this->lead_record));
        }

        return $this;
    }

    public function updateShippingAddress(array $shipping)
    {
        // There's a shipping address or skip.
        if((!is_null($this->shipping_address)) && (count($shipping) > 0))
        {
            // Check all applicable fields, skip all non-changes,
            $something_changed = false;
            foreach ($shipping as $col => $val)
            {
                if($this->shipping_address->$col != $val)
                {
                    $this->shipping_address->$col = $val;
                    $something_changed = true;
                }
            }

            if($something_changed)
            {
                $this->recordThat(new ShippingUpdated($this->shipping_address->id, $shipping));
            }
        }

        return $this;
    }

    public function updateBillingAddress(array $billing)
    {
        // There's a billing address or skip.
        if((!is_null($this->billing_address)) && (count($billing) > 0))
        {
            // Check all applicable fields, skip all non-changes,
            $something_changed = false;
            foreach ($billing as $col => $val)
            {
                if($this->billing_address->$col != $val)
                {
                    $this->billing_address->$col = $val;
                    $something_changed = true;
                }
            }

            if($something_changed)
            {
                $this->recordThat(new BillingUpdated($this->billing_address->id, $billing));
            }
        }

        return $this;
    }

    public function linkEmailToShipping($email) : self
    {
        if((!is_null($this->shipping_address)))
        {
            if($this->shipping_address->email != $email)
            {
                $this->recordThat(new EmailToAddressLinkBecameAvailable($email, $this->shipping_address, $this->billing_address));

                $this->shipping_address->email = $email;
            }
        }


        return $this;
    }

    public function linkEmailToBilling($email) : self
    {
        if((!is_null($this->billing_address)))
        {
            if($this->billing_address->email != $email)
            {
                $this->recordThat(new EmailToAddressLinkBecameAvailable($email, $this->shipping_address, $this->billing_address));
                $this->billing_address->email = $email;
            }
        }

        return $this;
    }

    public function createShopifyDraftOrder() : self
    {
        return $this;
    }

    // @todo - complete this method
    public function updateShopifyDraftOrder() : self
    {
        return $this;
    }

    //@todo - deprecate this method
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
            'shopify_customer' => (!is_null($this->shopify_customer)) ? $this->shopify_customer->toArray() : null
        ];

        $validated = Validator::make($req_map, [
            'line_items'       => 'required|array', // not empty
            'lead_record'      => 'required', // Is a lead model
            //'billing_phone'    => 'required|regex:/^(?:\([2-9]\d{2}\)\ ?|[2-9]\d{2}(?:\-?|\ ?))[2-9]\d{2}[- ]?\d{4}$/|min:10', // is a phone number
            //'shipping_phone'   => 'required|regex:/^(?:\([2-9]\d{2}\)\ ?|[2-9]\d{2}(?:\-?|\ ?))[2-9]\d{2}[- ]?\d{4}$/|min:10', // is a phone number
            'email_address'    => 'required|email:rfc,dns', // is an email address
            'billing_address'  => 'required', // is a BillingAddresses Model
            'shipping_address' => 'required', //is a ShippingAddresses model
            'shopify_customer' => 'required|array', // is a lead attributes value
        ]);

        if(!($failed = $validated->fails()))
        {
            // check for a DraftOrder record in lead_attributes
            $customer_attr = $this->lead_record->attributes()
                ->whereName('shopifyDraftOrder')
                ->first();

            $products = [
                'products' => $req_map['line_items'],
                'customer' => $req_map['shopify_customer']
            ];

            if(is_null($customer_attr))
            {
                // if not exists, trigger ShopifyDraftOrderCreated

                $this->recordThat(new ShopifyDraftOrderCreated($this->lead_record, $products));
                $this->persist();
            }
            else
            {
                // if exists, @todo - check if there are any changes needing to be made
                // trigger ShopifyDraftOrderUpdated
                $this->recordThat(new ShopifyDraftOrderUpdated($this->lead_record, $products));
                $this->persist();
            }

        }

        return $this;
    }

    public function addToLeadAttributes(array $payload = [])
    {
        $this->lead_attributes = $this->lead_record->attributes()->get();
        $this->lead_attributes = $this->lead_attributes->toArray();

        if((!is_null($this->lead_record)) && (count($payload) > 0))
        {

        }
    }
}
