<?php

namespace App\Services\PaymentGateways\CreditCard;

use App\Models\PaymentGateways\ClientEnabledPaymentProviders;
use App\Models\Sales\Orders;
use Stripe\StripeClient;

class StripeGateway implements CreditCardGateway
{
    protected $client_enabled_uuid;
    protected $stripe_client, $secret_key;

    public function __construct($client_enabled_uuid)
    {
        $this->client_enabled_uuid = $client_enabled_uuid;
        $this->initStripeClient();
    }

    private function initStripeClient()
    {
        $client_enabled_record = ClientEnabledPaymentProviders::find($this->client_enabled_uuid);

        if((!is_null($client_enabled_record)) && ($client_enabled_record->active == 1))
        {
            $this->secret_key = $client_enabled_record->misc['stripeSecretKey'];
            $this->stripe_client = new StripeClient($client_enabled_record->misc['stripeAPIKey']);
        }
    }

    public function authorize(array $details)
    {
        $results = ['success' => false, 'reason' => 'StripeGateway Not Enabled.'];

        if(!is_null($this->stripe_client))
        {
            // Get order with the orderUuid with the billing_address or fail
            $order = Orders::whereId($details['orderUuid'])
                ->with('shipping_address')->first();

            if(!is_null($order))
            {
                // Populate the payload for a token
                $expy = explode('/', $details['ccExpy']);
                $payload = [
                    'card' => [
                        'number' => $details['cc'],
                        'exp_month' => $expy[0],
                        'exp_year' => $expy[1],
                        'cvc' => $details['ccCvv']
                    ]
                ];

                // Send to stripe to get the source or fail
                $token_response = $this->stripe_client->tokens->create($payload);

                if($token_response && (array_key_exists('id', $token_response->toArray())))
                {
                    $token_response = $token_response->toArray();
                    // Make another payload to set up the auth.
                    $shop = $order->shop()->first();

                    $payload = [
                        'amount' => $details['price'] * 100,
                        'currency' => 'usd',
                        'source' => $token_response['id'],
                        'description' => 'Purchase from '.$shop->name,
                        'shipping' => [
                            'name' => "{$order->shipping_address->first_name} {$order->shipping_address->last_name}",
                            'address' => [
                                'line1' => $order->shipping_address->address,
                                'city' => $order->shipping_address->city,
                                'country' => $order->shipping_address->country,
                                //'line2' => $order->shipping_address->apt,
                                'postal_code' => $order->shipping_address->zip,
                                'state' => $order->shipping_address->state
                            ],
                            'phone' => $order->shipping_address->phone
                        ],
                        'capture' => false
                    ];

                    // Send the auth to stripe or fail
                    $auth_response = $this->stripe_client->charges->create($payload, ['api_key' => $this->secret_key]);

                    if($auth_response && (array_key_exists('id', $auth_response->toArray())))
                    {
                        $auth_response = $auth_response->toArray();
                        // Make a response that emulates the response from dry run
                        $results = ['success' => true, 'authorization' => [
                            'status' => 'authorized',
                            'price'  => $details['price'],
                            'date'   => date('Y-m-d h:m:s'),
                            'auth_id'=> $token_response['id'],
                            'capture_token' => $auth_response['id'],
                            'misc' => [
                                'token_response' => $token_response,
                                'auth_response' => $auth_response
                            ]
                        ]];
                    }
                    else
                    {
                        $results['reason'] = 'Declined';
                    }
                }
                else
                {
                    $results['reason'] = 'Token Authorization Failed';
                }
            }
            else
            {
                $results['reason'] = 'Invalid order for gateway';
            }
        }

        return $results;
    }

    public function capture(array $details)
    {
        $results = ['success' => false, 'reason' => 'StripeGateway Not Enabled.'];

        if(!is_null($this->stripe_client))
        {
            if(array_key_exists('capture_token', $details))
            {
                $payload = [
                    'amount' => $details['price'] * 100,
                ];

                $cap_response = $this->stripe_client->charges->capture(
                    $details['capture_token'],
                    $payload,
                    ['api_key' => $this->secret_key]
                );

                if($cap_response && (array_key_exists('id', $cap_response->toArray())))
                {
                    $cap_response = $cap_response->toArray();
                    $results = ['success' => true, 'sale' => [
                        'status' => 'captured',
                        'price'  => $details['price'],
                        'date'   => date('Y-m-d h:m:s'),
                        'auth_id'=> $details['capture_token'],
                        'sale_id' => $cap_response['id'],
                        'misc' => [
                            'cap_response' => $cap_response,
                        ]
                    ]];
                }
            }
        }

        return $results;
    }
}
