<?php

namespace App\Actions\Leads;

use App\Leads;
use App\BillingAddresses;
use App\ShippingAddresses;
use App\Aggregates\Orders\ShopifyOrderAggregate;

class CreateLeadByShippingAddress extends CreateOrUpdateLeadBaseAction
{
    protected $shipping, $leads, $billing;

    public function __construct(ShippingAddresses $shipping, Leads $leads, BillingAddresses $billing)
    {
        parent::__construct();
        $this->shipping = $shipping;
        $this->leads = $leads;
        $this->billing = $billing;
    }

    public function execute($payload = null)
    {
        $results = false;

        if(!is_null($payload))
        {
            $checkoutId = $payload['checkoutId'];
            $checkoutType = $payload['checkoutType'];

            if($checkout_details = $this->getCheckoutTypeDetails($checkoutType, $checkoutId))
            {
                $lead = $this->leads;
                $lead->reference_type = $checkoutType;
                $lead->reference_uuid = $checkoutId;

                $lead->first_name = $payload['shipping']['first_name'];
                $lead->last_name  = $payload['shipping']['last_name'];
                $lead->phone      = $payload['shipping']['phone'];

                $lead->shop_uuid = $checkout_details['shop_id'];
                $lead->merchant_uuid = $checkout_details['merchant_id'];
                $lead->client_uuid = $checkout_details['client_id'];

                // Create the lead with the email or fail
                if($lead->save())
                {
                    $results = $lead->toArray();

                    $aggy = ShopifyOrderAggregate::retrieve($lead->id)
                        ->addLeadRecord($lead)
                        ->addLineItems($checkout_details['products'])
                        ->addContactOptin($payload['emailList']);

                    $payload['shipping']['client_uuid'] = $lead->client_uuid;
                    $payload['shipping']['merchant_uuid'] = $lead->merchant_uuid;
                    $payload['shipping']['shop_uuid'] = $lead->shop_uuid;
                    // add the Shipping Address
                    $shipping = new $this->shipping($payload['shipping']);
                    $shipping->save();
                    $aggy = $aggy->addShippingAddress($shipping);

                    $billing = null;

                    if(array_key_exists('billing', $payload))
                    {
                        $payload['billing']['client_uuid'] = $lead->client_uuid;
                        $payload['billing']['merchant_uuid'] = $lead->merchant_uuid;
                        $payload['billing']['shop_uuid'] = $lead->shop_uuid;

                        // add the Billing Address
                        $billing = new $this->billing($payload['billing']);
                        $billing->save();
                        $aggy = $aggy->addBillingAddress($billing);
                    }

                    // Let's go ahead and make it all happen then we'll go about our business
                    $aggy->persist();

                    $results = [
                        'lead' => $lead,
                        'shipping' => $shipping,
                        'billing' => $billing
                    ];
                }
            }
        }

        return $results;
    }
}
