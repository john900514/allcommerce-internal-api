<?php

namespace App\Actions\Leads;

use App\Aggregates\Orders\ShopifyOrderAggregate;
use App\Leads;
use App\Emails;
use App\LeadAttributes;
use App\CheckoutFunnels;

class CreateOrUpdateLeadByEmail extends CreateOrUpdateLeadBaseAction
{
    protected $emails, $leads, $lead_attrs;

    public function __construct(Emails $emails, Leads $leads, LeadAttributes $lead_attrs)
    {
        parent::__construct();
        $this->leads = $leads;
        $this->emails = $emails;
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
                    // Check if the email exists in the emails table or skip
                    $email_address = $payload['attributes']['value'];
                    $email_model = $this->emails->whereEmail($email_address)->first();

                    if(!is_null($email_model))
                    {
                        // This is the new order flow, so don't look up the old lead.
                        $lead = $this->createLead($payload, $deets);
                        /*
                        // If email record exists, cross-reference the leads table for record or skip
                        if($lead = $this->leads->findLeadViaEmailAddress($email_address, $shop_id, $merchant_id, $client_id))
                        {
                            // If leads record exists, send to update to lead
                            $lead = $this->updateLead($payload, $deets, 'email', $lead);
                        }
                        else
                        {
                            // send to create lead
                            $lead = $this->createLead($payload, $deets);
                        }
                        */
                    }
                    else
                    {
                        // send to create lead
                        $lead = $this->createLead($payload, $deets);

                        // fire email tables job.
                        // $this->triggerEmailTableCheck($email_address, $deets);
                    }
                }

                if($lead)
                {
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

                    // attempt to locate the shipping address here
                    $shipping = $lead->shipping_address()->first();
                    if(!is_null($shipping))
                    {
                        $results['shipping_address'] = $shipping->toArray();
                    }

                    // attempt to locate the billing address here
                    $billing = $lead->billing_address()->first();
                    if(!is_null($billing))
                    {
                        $results['billing_address'] = $billing->toArray();
                    }

                    $this->runPostProcessing($payload, $lead, $deets);
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
        // create the lead record
        $lead = new $this->leads;
        $lead->reference_type = $attrs['checkoutType'];
        $lead->reference_uuid = $attrs['checkoutId'];

        $lead->email = $attrs['value'];

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

            $this->event_aggregate = ShopifyOrderAggregate::retrieve($lead->id)
                ->addLeadRecord($lead)
                ->addLineItems($checkout_details['products']);
        }

        return $results;
    }

    private function updateLead(array $payload, $checkout_details, $method = 'lead_uuid', Leads $lead = null)
    {
        $results = false;

        if(is_null($lead))
        {
            // locate the lead record or lookup by email address (fail if both fail)
            if($method == 'lead_uuid')
            {
                $lead = $this->leads->find($payload['lead_uuid']);
            }
            else
            {
                $lead = $this->leads->findLeadViaEmailAddress($payload['attributes']['value'], $checkout_details['shop_id'], $checkout_details['merchant_id'], $checkout_details['client_id']);
            }
        }

        if(!is_null($lead))
        {
            $this->event_aggregate = ShopifyOrderAggregate::retrieve($lead->id)
                ->addLeadRecord($lead, false)
                ->addLineItems($checkout_details['products']);

            $lead->reference_type = $payload['attributes']['checkoutType'];
            $lead->reference_uuid = $payload['attributes']['checkoutId'];

            // add the name and phone here with the shipping since that's the person getting the stuff
            $lead->email = $payload['attributes']['value'];

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
