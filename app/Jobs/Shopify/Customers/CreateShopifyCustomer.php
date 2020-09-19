<?php

namespace App\Jobs\Shopify\Customers;

use App\Leads;
use App\LeadAttributes;
use App\ShopifyInstalls;
use App\BillingAddresses;
use App\ShippingAddresses;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\Shopify\ShopifyAdminAPIService;

class CreateShopifyCustomer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $shipping, $billing, $lead, $service;
    /**
     * Create a new event instance.
     * @param ShippingAddresses $shipping
     * @param BillingAddresses $billing
     * @param Leads $lead
     * @return void
     */
    public function __construct(ShippingAddresses $shipping, BillingAddresses $billing, Leads $lead)
    {
        $this->shipping = $shipping;
        $this->billing = $billing;
        $this->lead = $lead;
    }

    /**
     * Execute the job.
     * @param LeadAttributes $lead_attrs
     * @param ShopifyAdminAPIService $service
     * @return void
     */
    public function handle(LeadAttributes $lead_attrs, ShopifyAdminAPIService $service)
    {
        // Get the shop's install record to get the deets or quit
        if(!is_null($install = $this->lead->shop_install()->first()))
        {
            //Call Shopify to see if the customer exist (they might be a repeat customer)
            $this->service = $service;
            $customer = $this->getCustomerFromShopify($install);

            if(!$customer)
            {
                // If not success, Call Shopify to create the customer with available data
                $customer = $this->createCustomerFromShopify($install);
            }
            else
            {
                // update the customer data to shopify
                $customer = $this->updateCustomerFromShopify($install, $customer);
            }

            if($customer)
            {
                // If success create lead_attribute record
                $customer_attr = $this->lead->attributes()
                    ->whereName('shopifyCustomer')
                    ->first();

                if(is_null($customer_attr))
                {
                    $customer_attr = new $lead_attrs;
                }

                if(!is_null($customer_attr))
                {
                    $customer_attr->lead_uuid = $this->lead->id;
                    $customer_attr->name = 'shopifyCustomer';
                    $customer_attr->value = $customer['id'];
                    $customer_attr->misc = $customer;
                    $customer_attr->active = 1;
                    $customer_attr->shop_uuid = $this->lead->shop_uuid;
                    $customer_attr->merchant_uuid = $this->lead->merchant_uuid;
                    $customer_attr->client_uuid = $this->lead->client_uuid;
                }

                $customer_attr->save();
            }
        }

    }

    private function updateCustomerFromShopify(ShopifyInstalls $install, $shopify_customer)
    {
        $results = false;

        $payload = [
            'customer' => [
                'id' => $shopify_customer['id'],
                'first_name' => $this->lead->first_name,
                'last_name'  => $this->lead->last_name,
                'addresses'  => [
                    [
                        'address1' => $this->shipping->address,
                        'city' => $this->shipping->city,
                        'state' => $this->shipping->state,
                        'zip' => $this->shipping->zip,
                        'first_name' => $this->shipping->first_name,
                        'last_name'  => $this->shipping->last_name,
                        'country' => $this->shipping->country,
                    ],
                    [
                        'address1' => $this->billing->address,
                        'city' => $this->billing->city,
                        'state' => $this->billing->state,
                        'zip' => $this->billing->zip,
                        'first_name' => $this->billing->first_name,
                        'last_name'  => $this->billing->last_name,
                        'country' => $this->billing->country,
                    ]
                ]
            ]
        ];

        if(!is_null($this->lead->email))
        {
            $payload['customer']['email'] = $this->lead->email;
        }

        if(!is_null($this->shipping->email))
        {
            $payload['customer']['addresses'][0]['email'] = $this->shipping->email;
        }

        if(!is_null($this->billing->email))
        {
            $payload['customer']['addresses'][1]['email'] = $this->billing->email;
        }

        if(!is_null($this->lead->phone))
        {
            $payload['customer']['phone'] = $this->lead->phone;
        }

        if(!is_null($this->shipping->phone))
        {
            $payload['customer']['addresses'][0]['phone'] = $this->shipping->phone;
        }

        if(!is_null($this->billing->phone))
        {
            $payload['customer']['addresses'][1]['phone'] = $this->billing->phone;
        }

        $response = $this->service->updateCustomer($install, $payload);

        if($response)
        {
            if(array_key_exists('customer', $response))
            {
                $results = $response['customer'];
            }
        }

        return $results;
    }

    private function createCustomerFromShopify(ShopifyInstalls $install)
    {
        $results = false;

        $payload = [
            'customer' => [
                'first_name' => $this->lead->first_name,
                'last_name'  => $this->lead->last_name,
                'addresses'  => [
                    [
                        'address1' => $this->shipping->address,
                        'city' => $this->shipping->city,
                        'state' => $this->shipping->state,
                        'zip' => $this->shipping->zip,
                        'first_name' => $this->shipping->first_name,
                        'last_name'  => $this->shipping->last_name,
                        'country' => $this->shipping->country,
                    ],
                    [
                        'address1' => $this->billing->address,
                        'city' => $this->billing->city,
                        'state' => $this->billing->state,
                        'zip' => $this->billing->zip,
                        'first_name' => $this->billing->first_name,
                        'last_name'  => $this->billing->last_name,
                        'country' => $this->billing->country,
                    ]
                ]
            ]
        ];

        if(!is_null($this->lead->email))
        {
            $payload['customer']['email'] = $this->lead->email;
        }

        if(!is_null($this->lead->phone))
        {
            $payload['customer']['phone'] = $this->lead->phone;
        }

        if(!is_null($this->shipping->email))
        {
            $payload['customer']['addresses'][0]['email'] = $this->shipping->email;
        }

        if(!is_null($this->billing->email))
        {
            $payload['customer']['addresses'][1]['email'] = $this->billing->email;
        }

         $response = $this->service->postCustomer($install, $payload);

        if($response)
        {
            if(array_key_exists('customer', $response))
            {
                $results = $response['customer'];
            }
        }

        return $results;
    }

    private function getCustomerFromShopify(ShopifyInstalls $install)
    {
        $results = false;

        if(!is_null($this->lead->email))
        {
            $query = 'query=email:'.$this->lead->email;
            $response1  = $this->service->getCustomer($install, $query);

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

        if((!is_null($this->lead->phone)) && (!$results))
        {
            $query = 'query=phone:'.$this->lead->phone;
            $response2  = $this->service->getCustomer($install, $query);

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
}
