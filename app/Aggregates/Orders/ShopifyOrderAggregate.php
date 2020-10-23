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
use App\Events\Leads\LeadUpdated;
use App\Events\Leads\LineItemsAdded;
use App\Events\Orders\LeadConvertedToOrder;
use App\Events\Orders\OrderPaymentAuthorized;
use App\Events\Shopify\ShopifyCustomerCreated;
use App\Events\Shopify\ShopifyCustomerUpdated;
use App\Events\Shopify\ShopifyDraftOrderCreated;
use App\Events\Shopify\ShopifyDraftOrderUpdated;
use App\LeadAttributes;
use App\Leads;
use App\Models\Sales\Orders;
use App\Models\Sales\Transactions;
use App\ShippingAddresses;
use Illuminate\Support\Facades\Validator;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;

class ShopifyOrderAggregate extends AggregateRoot
{
    protected static bool $allowConcurrency = true;

    private $line_items = [];
    private $lead_record, $order_record, $transaction_record;
    private $email_address, $contact_optin;
    private $lead_attributes = [];
    private $shipping_address;
    private $billing_address;
    private $shopify_customer, $shopify_draft_order, $shopify_order;
    private $payment_authorized = false;
    private $payment_captured = false;
    private $order_closed = false;


    // APPLY functions for state
    public function applyLeadCreated(LeadCreated $event)
    {
        $this->lead_record = $event->getLead();
    }

    public function applyLeadUpdated(LeadUpdated $event)
    {
        $this->lead_record = $event->getLead();
    }

    public function applyLineItemsAdded(LineItemsAdded $event)
    {
        // curate this.
        $products = $event->getProducts();
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
    }

    public function applyEmailCreated(EmailCreated $event)
    {
        $this->email_address = $event->getEmail();
    }

    public function applyEmailUpdated(EmailUpdated $event)
    {
        $this->email_address = $event->getEmail();
    }

    public function applyCustomerOptedIntoCommunication(CustomerOptedIntoCommunication $event)
    {
        $this->contact_optin = $event->getOptin();
    }

    public function applyShippingCreated(ShippingCreated $event)
    {
        $this->shipping_address = $event->getShipping();
    }

    public function applyShippingUpdated(ShippingUpdated $event)
    {
        $shipping_id = $event->getShippingUuid();
        $shipping = ShippingAddresses::find($shipping_id);
        $this->shipping_address = $shipping;
    }

    public function applyBillingUpdated(BillingUpdated $event)
    {
        $billing_id = $event->getBillingUuid();
        $billing = BillingAddresses::find($billing_id);
        $this->billing_address = $billing;
    }

    public function applyBillingCreated(BillingCreated $event)
    {
        $this->billing_address = $event->getBilling();
    }

    public function applyEmailToAddressLinkBecameAvailable(EmailToAddressLinkBecameAvailable $event)
    {
        $this->shipping_address->email = $event->getEmail();
        $this->billing_address->email = $event->getEmail();
    }

    public function applyShopifyCustomerCreated(ShopifyCustomerCreated $event)
    {
        $this->shopify_customer = $event->getDetails();
    }

    public function applyShopifyCustomerUpdated(ShopifyCustomerUpdated $event)
    {
        $this->shopify_customer = $event->getDetails();
    }

    public function applyShopifyDraftOrderCreated(ShopifyDraftOrderCreated $event)
    {
        $this->shopify_draft_order = $event->getCheckoutDetails();
    }

    public function applyShopifyDraftOrderUpdated(ShopifyDraftOrderUpdated $event)
    {
        $this->shopify_draft_order = $event->getCheckoutDetails();
    }

    public function applyLeadConvertedToOrder(LeadConvertedToOrder $event)
    {
        $this->order_record = $event->getOrder();
    }

    public function applyOrderPaymentAuthorized(OrderPaymentAuthorized $event)
    {
        $this->payment_authorized = true;
        $this->transaction_record = $event->getTransaction();
    }

    public function apply() {}

    // ACTION functions for state change
    public function addLeadRecord(Leads $lead, $record = true)
    {
        $this->recordThat(new LeadCreated($lead));

        return $this;
    }

    public function addLineItems(array $products)
    {
        $this->recordThat(new LineItemsAdded($this->lead_record, $products));

        return $this;
    }

    public function addEmailAddress($email)
    {
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
                    $this->recordThat(new EmailUpdated($email, $email_record, $this->lead_record));
                }
            }

        }

        return $this;
    }

    public function addContactOptin(bool $optin) : self
    {
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
        }
        else
        {
            $this->recordThat(new CustomerOptedIntoCommunication($this->lead_record, $optin));
        }

        return $this;
    }

    public function addShippingAddress(ShippingAddresses $shipping, $record = true)
    {
        $this->recordThat(new ShippingCreated($shipping, $this->lead_record));

        return $this;
    }

    public function addBillingAddress(BillingAddresses $billing, $record = true)
    {
        $this->recordThat(new BillingCreated($billing, $this->lead_record));

        return $this;
    }

    public function updateLeadRecord(Leads $lead)
    {
        $this->recordThat(new LeadUpdated($lead));

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
            }
        }

        return $this;
    }

    public function addShopifyCustomerLeadAttribute($details, $record = true) : self
    {
        $this->recordThat(new ShopifyCustomerCreated($details, $this->lead_record));

        return $this;
    }

    public function addShopifyDraftOrderAttribute($details, $record = true) : self
    {
        if($record) {
            // create a new event. stash, $lead_uuid, payload array (hopefully it serializes lol).
            $this->recordThat(new ShopifyDraftOrderCreated($this->lead_record, $details));
        }

        return $this;
    }

    public function  updateShopifyDraftOrderAttribute($details, $record = true) : self
    {
        $this->recordThat(new ShopifyDraftOrderUpdated($this->lead_record, $details));

        return $this;
    }

    public function updateShopifyCustomerLeadAttribute($details)
    {
        $this->recordThat(new ShopifyCustomerUpdated($this->shipping_address, $this->billing_address, $this->lead_record, $details));

        return $this;
    }

    public function createOrUpdateDraftOrder()
    {
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

    public function leadIsNowOrder(Orders $order)
    {
        $this->recordThat(new LeadConvertedToOrder($order, $this->lead_record));

        return $this;
    }

    public function processPostAuth(Transactions $transaction)
    {
        $this->recordThat(new OrderPaymentAuthorized($transaction));

        return $this;
    }

    public function getLead()
    {
        return $this->lead_record;
    }

    public function getOrder()
    {
        return $this->order_record;
    }

    public function getLineItems()
    {
        return $this->line_items;
    }

    public function getShopifyCustomer()
    {
        return $this->shopify_customer;
    }

    public function getShopifyDraftOrder()
    {
        return $this->shopify_draft_order;
    }

    public function getSubTotal()
    {
        $results = false;

        if(!is_null($this->shopify_draft_order))
        {
            $results = floatval($this->shopify_draft_order['subtotal_price']);
        }

        return $results;
    }

    public function getTax()
    {
        $results = false;

        if(!is_null($this->shopify_draft_order))
        {
            $results = floatval($this->shopify_draft_order['total_tax']);
        }

        return $results;
    }

    public function getShippingPrice()
    {
        $results = false;

        if(!is_null($this->shopify_draft_order))
        {
            $results = floatval($this->shopify_draft_order['shipping_line']['price']);
        }

        return $results;
    }
}
