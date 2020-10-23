<?php

namespace App\Actions\Payments\Credit;

use App\Actions\Action;
use App\Aggregates\Orders\ShopifyOrderAggregate;
use App\CheckoutFunnels;
use App\Models\Sales\Transactions;
use Illuminate\Support\Facades\Validator;

class AuthorizeCreditCardPayment implements Action
{
    protected $funnels, $transactions;

    public function __construct(CheckoutFunnels $funnels, Transactions $transactions)
    {
        $this->funnels = $funnels;
        $this->transactions = $transactions;
    }

    public function execute($payload = null)
    {
        $results = ['success' => false];
        $platform = 'Unknown';

        if(!is_null($payload))
        {
            $validated = Validator::make($payload, [
                'leadUuid'  => 'bail|required|exists:leads,id',
                'orderUuid' => 'bail|required|exists:orders,id',
                'cc'        => 'bail|required|numeric',
                'ccName'    => 'bail|required',
                'ccExpy'    => 'bail|required',
                'ccCvv'     => 'bail|required|numeric',
                'shopifyDraftOrderId' => 'sometimes|required',
                'price'     => 'bail|required|numeric',
            ]);

            // Validate the request or fail with reason
            if($validated->fails())
            {
                foreach($validated->errors()->toArray() as $col => $msg)
                {
                    $results['reason'] = $msg[0];
                    break;
                }
            }
            else
            {
                // Turn on $aggy, the event-aggregator for the Order
                if(array_key_exists('shopifyDraftOrderId', $payload))
                {
                    $platform = 'Shopify';
                    $aggy = ShopifyOrderAggregate::retrieve($payload['leadUuid']);
                }
                else
                {
                    // @todo - support order aggregates from other platforms
                }

                // Make sure the lead's order_uuid is the passed in order_id or fail, no reason
                if(!is_null($order = $aggy->getOrder()) && ($order->id == $payload['orderUuid']))
                {
                    // @todo - Make sure this order has not already been completed before or fail
                    if(true)
                    {
                        // Make sure the order's shop is the session's active shop or fail, no reason
                        if($order->shop_uuid == session()->get('active_shop')->id)
                        {
                            // Check the order_reference, expect checkout_funnel or fail, no reason
                            $reference = false;
                            switch($order->reference_type)
                            {
                                case 'checkout_funnel':
                                    // Get the Checkout Funnel or fail, no reason
                                    $reference = $this->funnels->find($order->reference_uuid);
                                    break;
                            }

                            if($reference)
                            {
                                // Check for a checkout_funnel_payment_provider_override or skip
                                if($this->referenceHasPaymentGatewayOverride())
                                {
                                    // @todo - get the assigned provider for the checkout reference
                                }
                                else
                                {
                                    $procs = session()->get('active_shop')->shop_assigned_payment_providers()
                                        ->whereActive(1)
                                        ->get();
                                }

                                // If no override, check for the shop's shop_assigned_payment_processors, with relations, or fail
                                if(count($procs) > 0)
                                {
                                    $gateway = false;

                                    foreach ($procs as $processor)
                                    {
                                        if($processor->payment_provider->payment_type->slug == 'credit')
                                        {
                                            $gateway = $processor;
                                            break;
                                        }
                                    }

                                    //Locate the assigned credit payment or fail
                                    if($gateway)
                                    {
                                        $class_record = $gateway->payment_provider->gateway_attributes()
                                            ->whereName('service-class')->first();

                                        // Determine the Service class to use and init with client ID or fail
                                        if(!is_null($class_record))
                                        {
                                            $payment_processor = new $class_record->value($gateway->client_enabled_uuid);

                                            // Finally perform the authorization or fail
                                            try
                                            {
                                                $auth_response = $payment_processor->authorize($payload);
                                            }
                                            catch(\Exception $e)
                                            {
                                                $auth_response = ['success' => false, 'reason' => 'Error - '.$e->getMessage().' You were not charged.'];
                                            }


                                            if($auth_response['success'])
                                            {
                                                // On success, cut a transactions record
                                                $trans_data = [
                                                    'order_uuid' => $order->id,
                                                    'subtotal' => $aggy->getSubTotal(),
                                                    'tax' => $aggy->getSubTotal(),
                                                    'shipping' => $aggy->getShippingPrice(),
                                                    'total' => floatval($payload['price']),
                                                    'commission_rate' => 0,
                                                    'commission_amount' => 0,
                                                    'currency' => 'USD',
                                                    'symbol' => '$',
                                                    'platform_transaction_id' => null,
                                                    'shop_uuid' => $order->shop_uuid,
                                                    'merchant_uuid' => $order->merchant_uuid,
                                                    'client_uuid' => $order->client_uuid,
                                                    'misc' => [
                                                        'charge_type' => 'auth',
                                                        'platform' => 'Shopify',
                                                        'details' => $auth_response['authorization']
                                                    ]
                                                ];

                                                $transaction = $this->transactions->insert($trans_data);

                                                if($transaction)
                                                {
                                                    $results = ['success' => true, 'transaction' => $transaction->id];


                                                    // Give $aggy the good new and she'll fire the jobs that handle the rest so the customer doesn't have to wait!
                                                    $aggy->processPostAuth($transaction)
                                                        ->persist();
                                                }
                                            }
                                            else
                                            {
                                                /**
                                                 * STEPS
                                                 * 15. On fail, log the fail in the aggregate
                                                 */
                                            }
                                        }
                                        else
                                        {
                                            $results['reason'] = 'Payment Processor not Available.';
                                        }
                                    }
                                    else
                                    {
                                        // reason - "Shop not authorized for credit card payments"
                                        $results['reason'] = 'Shop not authorized for credit card payments';
                                    }
                                }
                                else
                                {
                                    // reason - "Shop not authorized for payments"
                                    $results['reason'] = 'Shop not authorized for payments';
                                }
                            }
                        }
                    }
                    else
                    {
                        $results['reason'] = 'This order is closed.';
                    }
                }
            }
        }

        return $results;
    }

    private function referenceHasPaymentGatewayOverride()
    {
        $results = false;

        return $results;
    }
}
