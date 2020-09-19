<?php

namespace App\Actions\Leads;

use App\Leads;
use App\Phones;
use App\LeadAttributes;
use App\BillingAddresses;
use App\ShippingAddresses;
use App\Aggregates\Orders\ShopifyOrderAggregate;

class CreateOrUpdateLeadByShippingAddress extends CreateOrUpdateLeadBaseAction
{
    protected $billing, $shipping, $phones, $leads, $lead_attrs;

    public function __construct(BillingAddresses $billing, ShippingAddresses $shipping, Phones $phones, Leads $leads, LeadAttributes $lead_attrs)
    {
        parent::__construct();
        $this->leads = $leads;
        $this->phones = $phones;
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
                    $phone_model = $this->phones->wherePhone($phone_no)->first();

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

                        // fire phone tables job.
                        // $this->triggerPhoneTableCheck($phone_no, $deets);
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
                    if(array_key_exists('shipping_uuid', $payload))
                    {
                        $shipping = $this->updateShippingAddress($payload, $lead->id, $deets);
                        $results['shipping_address'] = $shipping->toArray();
                    }
                    else
                    {
                        $shipping = $lead->shipping_address()->first();

                        if(is_null($shipping))
                        {
                            $shipping = $this->createShippingAddress($payload, $lead->id, $deets);
                            $results['shipping_address'] = $shipping->toArray();
                        }
                        else
                        {
                            $payload['shipping_uuid'] = $shipping->id;
                            $shipping = $this->updateShippingAddress($payload, $lead->id, $deets);
                            $results['shipping_address'] = $shipping->toArray();
                        }
                    }

                    // attempt to locate the billing address here
                    if(array_key_exists('billing_uuid', $payload))
                    {
                        $billing = $this->updateBillingAddress($payload, $lead->id, $deets);
                        $results['billing_address'] = $billing->toArray();
                    }
                    else
                    {
                        $billing = $lead->billing_address()->first();

                        if(is_null($billing))
                        {
                            $billing = $this->createBillingAddress($payload, $lead->id, $deets);
                            $results['billing_address'] = $billing->toArray();
                        }
                        else
                        {
                            $payload['billing_uuid'] = $billing->id;
                            $billing = $this->updateBillingAddress($payload, $lead->id, $deets);
                            $results['billing_address'] = $billing->toArray();
                        }
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
        $shipping = $attrs['value']['shipping'];
        $billing = $attrs['value']['billing'];

        $lead = new $this->leads;
        $lead->reference_type = $attrs['checkoutType'];
        $lead->reference_uuid = $attrs['checkoutId'];

        $lead->first_name = $shipping['shippingFirst'];
        $lead->last_name = $shipping['shippingLast'];
        $lead->phone = $shipping['shippingPhone'];

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
            $this->event_aggregate = ShopifyOrderAggregate::retrieve($lead->id)
                ->addLeadRecord($lead, false)
                ->addLineItems($checkout_details['products']);

            $lead->reference_type = $payload['attributes']['checkoutType'];
            $lead->reference_uuid = $payload['attributes']['checkoutId'];

            // add the name and phone here with the shipping since that's the person getting the stuff
            $shipping = $payload['attributes']['value']['shipping'];

            $lead->first_name = $shipping['shippingFirst'];
            $lead->last_name = $shipping['shippingLast'];
            $lead->phone = $shipping['shippingPhone'];

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

    private function createBillingAddress(array $payload, $lead_id, $checkout_details)
    {
        $data = $payload['attributes']['value']['billing'];

        $args = [
            'first_name'  => $data['billingFirst'],
            'last_name'   => $data['billingLast'],
            'phone'       => $data['billingPhone'],
            'address'     => $data['billingAddress'],
            'address2'    => array_key_exists('billingAddress2', $data) ? $data['billingAddress2'] : null,
            'apt'         => array_key_exists('billingApt', $data) ? $data['billingApt'] : null,
            //'company'         => array_key_exists('shippingCompany', $data) ? $data['shippingCompany'] : null,
            'city'        => $data['billingCity'],
            'state'       => $data['billingState'],
            'zip'         => $data['billingZip'],
            'country'     => $data['billingCountry'],
            // intentional pause for readability.
            'lead_uuid'     => $lead_id,
            'shop_uuid'     => $checkout_details['shop_id'],
            'merchant_uuid' => $checkout_details['merchant_id'],
            'client_uuid'   => $checkout_details['client_id'],
        ];

        return $this->billing->insert($args);
    }

    private function updateBillingAddress(array $payload, $lead_id, $checkout_details)
    {
        $results = false;

        if(array_key_exists('billing_uuid', $payload))
        {
            if(!is_null($billing = $this->billing->find($payload['billing_uuid'])))
            {
                $data = $payload['attributes']['value']['billing'];

                $args = [
                    'first_name'  => $data['billingFirst'],
                    'last_name'   => $data['billingLast'],
                    'phone'       => $data['billingPhone'],
                    'address'     => $data['billingAddress'],
                    'address2'    => array_key_exists('billingAddress2', $data) ? $data['billingAddress2'] : null,
                    'apt'         => array_key_exists('billingApt', $data) ? $data['billingApt'] : null,
                    //'company'         => array_key_exists('shippingCompany', $data) ? $data['shippingCompany'] : null,
                    'city'        => $data['billingCity'],
                    'state'       => $data['billingState'],
                    'zip'         => $data['billingZip'],
                    'country'     => $data['billingCountry'],
                    // intentional pause for readability.
                    'lead_uuid'     => $lead_id,
                    'shop_uuid'     => $checkout_details['shop_id'],
                    'merchant_uuid' => $checkout_details['merchant_id'],
                    'client_uuid'   => $checkout_details['client_id'],
                ];

                if($billing->updateMe($args))
                {
                    $results = $billing;
                }
            }
            else
            {
                $results = $this->createBillingAddress($payload, $lead_id, $checkout_details);
            }

        }
        else
        {
            $results = $this->createBillingAddress($payload, $lead_id, $checkout_details);
        }

        return $results;
    }

    private function createShippingAddress(array $payload, $lead_id, $checkout_details)
    {
        $data = $payload['attributes']['value']['shipping'];

        $args = [
            'first_name'  => $data['shippingFirst'],
            'last_name'   => $data['shippingLast'],
            'phone'       => $data['shippingPhone'],
            'address'     => $data['shippingAddress'],
            'address2'    => array_key_exists('shippingAddress2', $data) ? $data['shippingAddress2'] : null,
            'apt'         => array_key_exists('shippingApt', $data) ? $data['shippingApt'] : null,
            //'company'         => array_key_exists('shippingCompany', $data) ? $data['shippingCompany'] : null,
            'city'        => $data['shippingCity'],
            'state'       => $data['shippingState'],
            'zip'         => $data['shippingZip'],
            'country'     => $data['shippingCountry'],
            // intentional pause for readability.
            'lead_uuid'     => $lead_id,
            'shop_uuid'     => $checkout_details['shop_id'],
            'merchant_uuid' => $checkout_details['merchant_id'],
            'client_uuid'   => $checkout_details['client_id'],
        ];

        return $this->shipping->insert($args);
    }

    private function updateShippingAddress(array $payload, $lead_id, $checkout_details)
    {
        $results = false;

        if(array_key_exists('shipping_uuid', $payload))
        {
            if(!is_null($shipping = $this->shipping->find($payload['shipping_uuid'])))
            {
                $data = $payload['attributes']['value']['shipping'];

                $args = [
                    'first_name'  => $data['shippingFirst'],
                    'last_name'   => $data['shippingLast'],
                    'phone'       => $data['shippingPhone'],
                    'address'     => $data['shippingAddress'],
                    'address2'    => array_key_exists('shippingAddress2', $data) ? $data['shippingAddress2'] : null,
                    'apt'         => array_key_exists('shippingApt', $data) ? $data['shippingApt'] : null,
                    //'company'         => array_key_exists('shippingCompany', $data) ? $data['shippingCompany'] : null,
                    'city'        => $data['shippingCity'],
                    'state'       => $data['shippingState'],
                    'zip'         => $data['shippingZip'],
                    'country'     => $data['shippingCountry'],
                    // intentional pause for readability.
                    'lead_uuid'     => $lead_id,
                    'shop_uuid'     => $checkout_details['shop_id'],
                    'merchant_uuid' => $checkout_details['merchant_id'],
                    'client_uuid'   => $checkout_details['client_id'],
                ];

                if($shipping->updateMe($args))
                {
                    $results = $shipping;
                }
            }
            else
            {
                $results = $this->createShippingAddress($payload, $lead_id, $checkout_details);
            }
        }
        else
        {
            $results = $this->createShippingAddress($payload, $lead_id, $checkout_details);
        }

        return $results;
    }

}
