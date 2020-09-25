<?php

namespace App\Actions\Leads;

use App\Leads;
use App\BillingAddresses;
use App\ShippingAddresses;
use App\Aggregates\Orders\ShopifyOrderAggregate;

class UpdateLeadByShippingAddress extends CreateOrUpdateLeadBaseAction
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
            $lead = $this->leads->find($payload['lead_uuid']);

            if(!is_null($lead))
            {
                $lead_changed = false;

                if($lead->first_name != $payload['shipping']['first_name'])
                {
                    $lead->first_name = $payload['shipping']['first_name'];
                    $lead_changed = true;
                }

                if($lead->last_name != $payload['shipping']['last_name'])
                {
                    $lead->last_name = $payload['shipping']['last_name'];
                    $lead_changed = true;
                }

                if($lead->phone != $payload['shipping']['phone'])
                {
                    $lead->phone = $payload['shipping']['phone'];
                    $lead_changed = true;
                }

                // Update the lead if changed && Set up the aggregate
                if($lead_changed)
                {
                    $lead->save();

                    $aggy = ShopifyOrderAggregate::retrieve($lead->id)
                        ->addLeadRecord($lead);
                }
                else
                {
                    $aggy = ShopifyOrderAggregate::retrieve($lead->id)
                        ->addLeadRecord($lead, false);
                }

                // Use the lead aggregate to update the optin
                $aggy = $aggy->addContactOptin($payload['emailList']);

                // create or update the shipping
                $shipping = null;
                if(array_key_exists('shipping_uuid', $payload))
                {
                    // pull the Shipping Addresses record;
                    $shipping = $this->shipping->find($payload['shipping_uuid']);

                    if(!is_null($shipping))
                    {
                        $aggy = $aggy->addShippingAddress($shipping, false)
                            ->updateShippingAddress($payload['shipping']);
                    }
                    else
                    {
                        if(array_key_exists('shipping', $payload))
                        {
                            $payload['shipping']['client_uuid'] = $lead->client_uuid;
                            $payload['shipping']['merchant_uuid'] = $lead->merchant_uuid;
                            $payload['shipping']['shop_uuid'] = $lead->shop_uuid;
                            // add the Shipping Address
                            $shipping = new $this->shipping($payload['shipping']);
                            $shipping->save();
                            $aggy = $aggy->addShippingAddress($shipping);
                        }
                    }
                }
                else
                {
                    if(array_key_exists('shipping', $payload))
                    {
                        $payload['shipping']['client_uuid'] = $lead->client_uuid;
                        $payload['shipping']['merchant_uuid'] = $lead->merchant_uuid;
                        $payload['shipping']['shop_uuid'] = $lead->shop_uuid;
                        // add the Shipping Address
                        $shipping = new $this->shipping($payload['shipping']);
                        $shipping->save();
                        $aggy = $aggy->addShippingAddress($shipping);
                    }
                }

                // create or update the billing (if set)
                $billing = null;
                if(array_key_exists('billing_uuid', $payload))
                {
                    // pull the Billing Addresses record;
                    $billing = $this->billing->find($payload['billing_uuid']);

                    if(!is_null($billing))
                    {
                        $aggy = $aggy->addBillingAddress($billing, false)
                            ->updateBillingAddress($payload['billing']);
                    }
                    else
                    {
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

                    }
                }
                else
                {
                    if(array_key_exists('billing', $payload))
                    {
                        // add the Billing Address
                        $payload['billing']['client_uuid'] = $lead->client_uuid;
                        $payload['billing']['merchant_uuid'] = $lead->merchant_uuid;
                        $payload['billing']['shop_uuid'] = $lead->shop_uuid;

                        $billing = new $this->billing($payload['billing']);
                        $billing->save();
                        $aggy = $aggy->addBillingAddress($billing);
                    }
                }

                // link the email everywhere if its available!
                if(!is_null($lead->email))
                {
                    $aggy = $aggy->addEmailAddress($lead->email)
                        ->linkEmailToShipping($lead->email)
                        ->linkEmailToBilling($lead->email)
                    ;
                }

                if(!is_null($shipping))
                {
                    $yes = true;
                    if($yes)
                    {
                        $aggy = $aggy->createShopifyDraftOrder();
                    }
                    else
                    {
                        $aggy = $aggy->updateShopifyDraftOrder();
                    }
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

        return $results;
    }
}
