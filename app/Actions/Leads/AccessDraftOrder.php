<?php

namespace App\Actions\Leads;

use App\BillingAddresses;
use App\InventoryVariants;
use App\Leads;
use App\MerchantApiTokens;
use App\Aggregates\Orders\ShopifyOrderAggregate;
use App\Services\Shopify\ShopifyAdminAPIService;
use App\Services\Shopify\ShopifyDraftOrderService;
use App\ShippingAddresses;
use App\ShopifyInstalls;

class AccessDraftOrder extends CreateOrUpdateLeadBaseAction
{
    protected $leads, $tokens, $shopify;

    public function __construct(MerchantApiTokens $tokens, Leads $leads, ShopifyDraftOrderService $shopify)
    {
        parent::__construct();
        $this->tokens = $tokens;
        $this->leads = $leads;
        $this->shopify = $shopify;
    }

    public function execute($payload = null)
    {
        $results = false;

        if(!is_null($payload))
        {
            $lead = $this->leads->find($payload['leadUuid']);

            // Validate the lead belongs to the client attached to the token or fail.
            if(array_key_exists('token', $payload))
            {
                $token = $payload['token'];
                $token_record = $this->tokens->whereToken($token)->first();

                if(!is_null($token_record) && ($token_record->client_id == $lead->client_uuid))
                {
                    // Get the lead's shipping & billing or fail for not enough info
                    $billing = $lead->billing_address()->first();
                    $shipping = $lead->shipping_address()->first();

                    if((!is_null($billing)) && (!is_null($shipping)))
                    {
                        // Set up the aggregate with the lead data.
                        $aggy = ShopifyOrderAggregate::retrieve($lead->id)
                            //->addLeadRecord($lead)
                            //->addShippingAddress($shipping)
                            //->addBillingAddress($billing)
                        ;


                        $record_customer = false;
                        $customer_attr = $lead->attributes()->whereName('shopifyCustomer')->first();
                        $customer = null;

                        // check to see if there a shopifyCustomer
                        if(is_null($customer_attr))
                        {
                            // if no, create it and tag aggregate for it happening
                            if(!is_null($install = $lead->shop_install()->first()))
                            {
                                $customer = $this->getCustomerFromShopify($install, $lead);

                                if(!$customer)
                                {
                                    // If not success, Call Shopify to create the customer with available data
                                    $customer = $this->createCustomerFromShopify($install, $lead, $shipping, $billing);
                                }
                                else
                                {
                                    //  update the customer data here Justin Case data is different.
                                    $customer = $this->updateCustomerFromShopify($install, $lead, $shipping, $billing, $customer);
                                }

                                $record_customer = ($customer != false);
                            }
                        }
                        else
                        {
                            // if so, hang on to it
                            if(!is_null($install = $lead->shop_install()->first()))
                            {
                                $customer = $this->getCustomerFromShopify($install, $lead);

                                //  update the customer data here Justin Case data is different.
                                $customer = $this->updateCustomerFromShopify($install, $lead, $shipping, $billing, $customer);
                            }

                        }

                        if(!is_null($customer))
                        {
                            // tag customer in event-sourced aggregate
                            $deets = [
                                'customer' => $customer
                            ];

                            if($record_customer)
                            {
                                $aggy = $aggy->addShopifyCustomerLeadAttribute($deets, $record_customer);
                            }
                            else
                            {
                                $aggy = $aggy->updateShopifyCustomerLeadAttribute($deets);
                            }


                            // see if there is an DraftOrder lead attribute already
                            $draft_attr = $lead->attributes()
                                ->whereName('shopifyDraftOrder')
                                ->first();

                            if(is_null($draft_attr))
                            {
                                // Check the reference type of the lead, should be checkout_funnel or fail(unsupported)
                                // Get the checkout funnel details to get the product cart details
                                if($checkout_details = $this->getCheckoutTypeDetails($lead->reference_type, $lead->reference_uuid))
                                {
                                    $data = [
                                        'draft_order' => [
                                            'line_items' => $this->shopify->prepProducts(new InventoryVariants(), $checkout_details),
                                            'shipping_address' => $this->shopify->prepAddress($shipping),
                                            'billing_address' => $this->shopify->prepAddress($billing),
                                            'customer' => [
                                                'id' => intVal($customer['id'])
                                            ],
                                            'shipping_line' => [
                                                'custom' => true,
                                                'title'  => $payload['shippingMethod']['title'],
                                                'price'  => $payload['shippingMethod']['price']
                                            ]
                                        ]
                                    ];

                                    // synchronous hit up shopify to post up lead order with queried datas.
                                    $response = $this->shopify->postDraftOrder($install, $data);

                                    if($response && is_array($response) && array_key_exists('draft_order', $response))
                                    {
                                        $draft_order = $response['draft_order'];
                                        $aggy = $aggy->addShopifyDraftOrderAttribute($draft_order);
                                    }
                                }
                            }
                            else
                            {
                                // if so, hang on to it
                                if($checkout_details = $this->getCheckoutTypeDetails($lead->reference_type, $lead->reference_uuid))
                                {
                                    $data = [
                                        'draft_order' => [
                                            'id' => $draft_attr->value,
                                            'line_items' => $this->shopify->prepProducts(new InventoryVariants(), $checkout_details),
                                            'shipping_address' => $this->shopify->prepAddress($shipping),
                                            'billing_address' => $this->shopify->prepAddress($billing),
                                            'customer' => [
                                                'id' => intVal($customer['id'])
                                            ]
                                        ]
                                    ];

                                    if(array_key_exists('shippingMethod', $payload))
                                    {
                                        $data['shipping_line'] = [
                                            'custom' => true,
                                            'title'  => $payload['shippingMethod']['title'],
                                            'price'  => $payload['shippingMethod']['price']
                                        ];
                                    }

                                    // hit up shopify to update (PUT)
                                    $response = $this->shopify->updateDraftOrder($install, $data);

                                    if($response && is_array($response) && array_key_exists('draft_order', $response))
                                    {
                                        $draft_order = $response['draft_order'];
                                        $aggy = $aggy->updateShopifyDraftOrderAttribute($draft_order);
                                    }
                                }
                            }

                            if($draft_order)
                            {
                                // Return the data
                                $results = $draft_order;
                            }

                            $aggy->persist();
                        }
                    }
                }
            }
        }

        return $results;
    }

    private function getCustomerFromShopify(ShopifyInstalls $install, Leads $lead)
    {
        $results = false;

        if(!is_null($lead->email))
        {
            $query = 'query=email:'.$lead->email;
            $response1  = $this->shopify->getCustomer($install, $query);

            if($response1)
            {
                if(array_key_exists('customers', $response1))
                {
                    if(count($response1['customers']) > 0)
                    {
                        // @todo - attempt to find the customer? Hopefully there's only one!
                        $results = $response1['customers'][0];
                    }
                }
            }
        }

        if((!is_null($lead->phone)) && (!$results))
        {
            $query = 'query=phone:'.$lead->phone;
            $response2  = $this->shopify->getCustomer($install, $query);

            if($response2)
            {
                if(array_key_exists('customers', $response2))
                {
                    if(count($response2['customers']) > 0)
                    {
                        // @todo - attempt to find the customer? Hopefully there's only one!
                        $results = $response2['customers'][0];
                    }
                }
            }
        }

        return $results;
    }

    private function createCustomerFromShopify(ShopifyInstalls $install, Leads $lead, ShippingAddresses $shipping, BillingAddresses $billing)
    {
        $results = false;

        $payload = [
            'customer' => [
                'first_name' => $lead->first_name,
                'last_name'  => $lead->last_name,
                'addresses'  => [
                    [
                        'address1' => $shipping->address,
                        'city' => $shipping->city,
                        'state' => $shipping->state,
                        'zip' => $shipping->zip,
                        'first_name' => $shipping->first_name,
                        'last_name'  => $shipping->last_name,
                        'country' => $shipping->country,
                    ],
                    [
                        'address1' => $billing->address,
                        'city' => $billing->city,
                        'state' => $billing->state,
                        'zip' => $billing->zip,
                        'first_name' => $billing->first_name,
                        'last_name'  => $billing->last_name,
                        'country' => $billing->country,
                    ]
                ]
            ]
        ];

        if(!is_null($lead->email))
        {
            $payload['customer']['email'] = $lead->email;
        }

        if(!is_null($lead->phone))
        {
            $payload['customer']['phone'] = $lead->phone;
        }

        if(!is_null($shipping->email))
        {
            $payload['customer']['addresses'][0]['email'] = $shipping->email;
        }

        if(!is_null($billing->email))
        {
            $payload['customer']['addresses'][1]['email'] = $billing->email;
        }

        $response = $this->shopify->postCustomer($install, $payload);

        if($response)
        {
            if(array_key_exists('customer', $response))
            {
                $results = $response['customer'];
            }
        }

        return $results;
    }

    private function updateCustomerFromShopify(ShopifyInstalls $install, Leads $lead,  ShippingAddresses $shipping, BillingAddresses $billing, $shopify_customer)
    {
        $results = false;

        $payload = [
            'customer' => [
                'id' => $shopify_customer['id'],
                'first_name' => $lead->first_name,
                'last_name'  => $lead->last_name,
                'addresses'  => [
                    [
                        'address1' => $shipping->address,
                        'city' => $shipping->city,
                        'state' => $shipping->state,
                        'zip' => $shipping->zip,
                        'first_name' => $shipping->first_name,
                        'last_name'  => $shipping->last_name,
                        'country' => $shipping->country,
                    ],
                    [
                        'address1' => $billing->address,
                        'city' => $billing->city,
                        'state' => $billing->state,
                        'zip' => $billing->zip,
                        'first_name' => $billing->first_name,
                        'last_name'  => $billing->last_name,
                        'country' => $billing->country,
                    ]
                ]
            ]
        ];

        if(!is_null($lead->email))
        {
            $payload['customer']['email'] = $lead->email;
        }

        if(!is_null($shipping->email))
        {
            $payload['customer']['addresses'][0]['email'] = $shipping->email;
        }

        if(!is_null($billing->email))
        {
            $payload['customer']['addresses'][1]['email'] = $billing->email;
        }

        if(!is_null($lead->phone))
        {
            $payload['customer']['phone'] = $lead->phone;
        }

        if(!is_null($shipping->phone))
        {
            $payload['customer']['addresses'][0]['phone'] = $shipping->phone;
        }

        if(!is_null($billing->phone))
        {
            $payload['customer']['addresses'][1]['phone'] = $billing->phone;
        }

        $response = $this->shopify->updateCustomer($install, $payload);

        if($response)
        {
            if(array_key_exists('customer', $response))
            {
                $results = $response['customer'];
            }
        }

        return $results;
    }
}
