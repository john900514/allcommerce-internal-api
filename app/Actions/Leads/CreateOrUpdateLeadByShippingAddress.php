<?php

namespace App\Actions\Leads;

use App\Leads;
use App\LeadAttributes;
use App\Actions\Action;
use App\BillingAddresses;
use App\ShippingAddresses;

class CreateOrUpdateLeadByShippingAddress extends CreateOrUpdateLeadBaseAction
{
    protected $billing, $shipping, $leads, $lead_attrs;

    public function __construct(BillingAddresses $billing, ShippingAddresses $shipping, Leads $leads, LeadAttributes $lead_attrs)
    {
        $this->leads = $leads;
        $this->billing = $billing;
        $this->shipping = $shipping;
        $this->lead_attrs = $lead_attrs;
    }

    public function execute($payload = null)
    {
        $results = ['success' => false, 'reason' => 'Could not create or update lead.'];

        // Make sure payload is ready to fail
        if(!is_null($payload))
        {
            // Make sure the checkout type checks out or fail
            $checkoutId = $payload['attributes']['checkoutId'];
            $checkoutType = $payload['attributes']['checkoutType'];
            if($deets = $this->getCheckoutTypeDetails($checkoutType, $checkoutId))
            {
                $shop_id = $deets['shop_id'];
                $merchant_id = $deets['merchant_id'];
                $client_id = $deets['client_id'];

                // If lead uuid exists send to update lead or skip
                if(array_key_exists('lead_uuid', $payload))
                {
                    $lead = $this->updateLead($payload, $deets, 'lead_uuid');
                }
                else
                {
                    // Check if the phone exists in the phones table or skip
                    $phone_no = $payload['attributes']['value']['shipping']['shippingPhone'];
                    // @todo - phone_model = $this->phones->wherePhone($email_address)->first();
                    $phone_model = null;

                    if(!is_null($phone_model))
                    {
                        if($lead = $this->leads->findLeadViaPhoneNumber($phone_no, $shop_id, $merchant_id, $client_id))
                        {
                            // If leads record exists, send to update to lead
                            $lead = $this->updateLead($payload, $deets, 'phone', $lead);
                        }
                        else
                        {
                            // send to create lead
                            $lead = $this->createLead($payload, $deets);
                        }
                    }
                    else
                    {
                        // send to create lead
                        $lead = $this->createLead($payload, $deets);

                        // fire email tables job.
                        $this->triggerPhoneTableCheck($phone_no, $deets);
                    }
                }

                if($lead)
                {
                    $this->runPostProcessing($payload, $lead);

                    // @todo - populate shipping, and billing items, from doing the update logic.
                    $results = [
                        'success' => true,
                        'lead' => [
                            'id' => $lead->id,
                            'first_name' => $lead->first_name,
                            'last_name' => $lead->last_name,
                            'email' => $lead->email,
                            'phone' => $lead->phone,
                            'ip' => $lead->ip,
                            'utm' => $lead->utm,
                            'created_at' => $lead->created_at,
                            'last_updated' => $lead->updated_at,
                        ],
                        'shipping_address' => [],
                        'billing_address'  => [],
                        'attributes' => [],
                        'order' => [],
                        'shop_id' => $deets['shop_id'],
                        'merchant_id' => $deets['merchant_id'],
                        'client_id' => $deets['client_id'],
                        'products' => $deets['products']
                    ];

                    if(count($lead_attributes = $lead->attributes()->get()) > 0)
                    {
                        foreach ($lead_attributes as $la)
                        {
                            $ella = $la->toArray();
                            unset($ella['deleted_at']);
                            unset($ella['shop_uuid']);
                            unset($ella['merchant_uuid']);
                            unset($ella['client_uuid']);
                            $results['attributes'][] = $ella;
                        }
                    }

                    // @todo - attempt to locate the shipping address here
                    // @todo - attempt to locate the billing address here
                }
            }

        }
        else
        {
            $results['reason'] = 'Invalid Lead Payload';
        }

        return $results;
    }

    private function createLead(array $payload, $checkout_details)
    {
        $results = false;

        $attrs = $payload['attributes'];
        $shipping = $attrs['value']['shipping'];
        $billing = $attrs['value']['billing'];

        $lead = new $this->leads;
        $lead->reference_type = $attrs['checkoutType'];
        $lead->reference_uuid = $attrs['checkoutId'];
        $lead->first_name = $shipping['shippingFirst'];
        $lead->last_name = $shipping['shippingLast'];
        $lead->phone = $shipping['shippingPhone'];

        // @todo - update the shipping address with a sweet function here
        // @todo - update the billing address with a dope function here

        // @todo - add the billing and shipping UUIDs to the lead model here

        $lead->shop_uuid = $checkout_details['shop_id'];
        $lead->merchant_uuid = $checkout_details['merchant_id'];
        $lead->client_uuid = $checkout_details['client_id'];

        if(array_key_exists('ip', $attrs))
        {
            $lead->ip_address = $attrs['ip'];
        }

        if(array_key_exists('utm', $attrs))
        {
            $lead->utm = $attrs['utm'];
        }

        if(array_key_exists('misc', $attrs))
        {
            $lead->misc = $attrs['misc'];
        }

        if($lead->save())
        {
            $results = $lead;
        }

        return $results;
    }

    private function updateLead(array $payload, $checkout_details, $method = 'lead_uuid', Leads $lead = null)
    {
        $results = false;

        if(is_null($lead))
        {
            // locate the lead record or lookup by shipping phone (fail if both fail)
            if($method == 'lead_uuid')
            {
                $lead = $this->leads->find($payload['lead_uuid']);
            }
            else
            {
                $phone_no = $payload['attributes']['value']['shipping']['shippingPhone'];
                $lead = $this->leads->findLeadViaPhoneNumber($phone_no, $checkout_details['shop_id'], $checkout_details['merchant_id'], $checkout_details['client_id']);
            }
        }

        if(!is_null($lead))
        {
            $lead->reference_type = $payload['attributes']['checkoutType'];
            $lead->reference_uuid = $payload['attributes']['checkoutId'];

            // add the name and phone here with the shipping since that's the person getting the stuff
            $shipping = $payload['attributes']['value']['shipping'];
            $billing = $payload['attributes']['value']['billing'];
            $lead->first_name = $shipping['shippingFirst'];
            $lead->last_name = $shipping['shippingLast'];
            $lead->phone = $shipping['shippingPhone'];

            // @todo - update the shipping address with a cool function here
            // @todo - update the billing address with a next-level function here

            // @todo - add the billing and shipping UUIDs to the lead model here

            $lead->shop_uuid = $checkout_details['shop_id'];
            $lead->merchant_uuid = $checkout_details['merchant_id'];
            $lead->client_uuid = $checkout_details['client_id'];

            if(array_key_exists('ip', $payload['attributes']))
            {
                $lead->ip_address = $payload['attributes']['ip'];
            }

            if(array_key_exists('utm', $payload['attributes']))
            {
                $lead->utm = $payload['attributes']['utm'];
            }

            if(array_key_exists('misc', $payload['attributes']))
            {
                $lead->misc = $payload['attributes']['misc'];
            }

            if($lead->save())
            {
                $results = $lead;
            }
        }

        return $results;
    }



}
