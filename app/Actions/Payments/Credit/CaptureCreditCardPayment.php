<?php

namespace App\Actions\Payments\Credit;

use App\Actions\Action;
use App\Aggregates\Orders\ShopifyOrderAggregate;
use App\CheckoutFunnels;
use App\Models\PaymentGateways\ClientEnabledPaymentProviders;
use App\Models\PaymentGateways\PaymentProviders;
use App\Models\Sales\Transactions;
use Illuminate\Support\Facades\Validator;

class CaptureCreditCardPayment implements Action
{
    protected  $enabled_providers, $funnels, $transactions;

    public function __construct(CheckoutFunnels $funnels,
                                PaymentProviders $enabled_providers,
                                Transactions $transactions)
    {
        $this->funnels = $funnels;
        $this->transactions = $transactions;
        $this->enabled_providers = $enabled_providers;
    }

    public function execute($payload = null)
    {
        $results = ['success' => false];
        $platform = 'Unknown';

        if(!is_null($payload))
        {
            $validated = Validator::make($payload, [
                'transaction_uuid'  => 'bail|required|exists:transactions,id'
            ]);

            // Validate the transaction id or fail.
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
                // Confirm the transaction is associated with the active shop or fail
                $auth_transaction = $this->transactions->find($payload['transaction_uuid']);
                if($auth_transaction->shop_uuid == session()->get('active_shop')->id)
                {
                    // Pull the associated order and make sure its not closed or fail
                    $order = $auth_transaction->order()->first();
                    $completed = (!is_null($order->misc))
                        && (is_array($order->misc))
                        && (array_key_exists('closed', $order->misc))
                        && ($order->misc['closed'] == true);

                    if(!$completed)
                    {
                        // Check the transaction itself to make sure it the auth transaction or fail
                        if($auth_transaction->misc['charge_type'] == 'auth')
                        {
                            // Get the shop type (shopify)
                            $platform = $auth_transaction->misc['platform'];

                            // toggle $aggy - the checkout aggregate for the shop type
                            $aggy = false;
                            switch($platform)
                            {
                                case 'Shopify':
                                    $aggy = ShopifyOrderAggregate::retrieve($order['lead_uuid']);
                                    break;
                            }

                            if($aggy)
                            {
                                // get the enabled gateway and the auth details or fail
                                $gateway_record = $this->enabled_providers->whereId($auth_transaction->misc['enabled_gateway']['payment_provider']['id'])
                                    ->with('gateway_attributes')
                                    //->whereName('service-class')
                                    ->first();

                                if(!is_null($gateway_record))
                                {
                                    $class_record = $gateway_record->gateway_attributes->where('name', '=', 'service-class')->first();

                                    if(!is_null($class_record))
                                    {
                                        // Pass the auth details in tho the gateway's capture function or fail
                                        $payment_processor = new $class_record->value($auth_transaction->misc['enabled_gateway']['client_enabled_uuid']);

                                        // @todo - Finally perform the authorization or fail
                                        try
                                        {
                                            $auth_response = $payment_processor->capture($auth_transaction->misc['details']);
                                        }
                                        catch(\Exception $e)
                                        {
                                            $auth_response = ['success' => false, 'reason' => 'Error - '.$e->getMessage().' You were not charged.'];
                                        }

                                        if($auth_response['success'])
                                        {
                                            // On success, cut a transactions record
                                            $commission = $gateway_record->gateway_attributes->where('name', '=', 'Commission')->first();
                                            $commission_rate = floatval($commission->misc['percent']);
                                            $total_price = floatval($auth_transaction->misc['details']['price']);
                                            $commission_amt = number_format(floatval($total_price * $commission_rate), 2);
                                            $trans_data = [
                                                'order_uuid' => $order->id,
                                                'subtotal' => $aggy->getSubTotal(),
                                                'tax' => $aggy->getSubTotal(),
                                                'shipping' => $aggy->getShippingPrice(),
                                                'total' => $total_price,
                                                'commission_rate' => $commission_rate,
                                                'commission_amount' => $commission_amt,
                                                'currency' => 'USD',
                                                'symbol' => '$',
                                                'platform_transaction_id' => $aggy->getShopifyOrder()['id'],
                                                'shop_uuid' => $order->shop_uuid,
                                                'merchant_uuid' => $order->merchant_uuid,
                                                'client_uuid' => $order->client_uuid,
                                                'misc' => [
                                                    'charge_type' => 'capture',
                                                    'platform' => $platform,
                                                    'enabled_gateway' => $auth_transaction->misc['enabled_gateway'],
                                                    'details' => $auth_response['sale']
                                                ]
                                            ];

                                            $sale_transaction = $this->transactions->insert($trans_data);

                                            if($sale_transaction)
                                            {
                                                // if success, and Shopify, locate the shopifyOrder
                                                $shopifyOrder = $aggy->getShopifyOrder();

                                                // send back the order_status_url
                                                $results = [
                                                    'success' => true,
                                                    'sale_transaction' => $sale_transaction->id,
                                                    'success_url' => $shopifyOrder['order_status_url']
                                                ];

                                                //tell $aggy the order is complete
                                                $aggy->processPostCapture($sale_transaction)
                                                    ->persist();
                                            }
                                        }
                                        else
                                        {
                                            // if failed, an Shopify, locate the shopifyOrder and
                                            $shopifyOrder = $aggy->getShopifyOrder();

                                            // send back the order_status_url
                                            $results = [
                                                'success' => true,
                                                'sale_transaction' => 0,
                                                'success_url' => $shopifyOrder->order_status_url
                                            ];
                                            /**
                                             * STEPS
                                             * 7. tell $aggy that order is completed but could not be captured
                                             * 8. update that status,
                                             * @todo - solution for letting the client know on dashboard as perhaps a event-sourcing reactor
                                             */
                                        }
                                    }
                                }
                                else
                                {
                                    $results['reason'] = 'Payment Processor not Available.';
                                }
                            }
                        }
                        else
                        {
                            $results['reason'] = 'Invalid Transaction';
                        }
                    }
                    else
                    {
                        $results['reason'] = 'Order Closed';
                    }
                }
            }
        }

        return $results;
    }
}
