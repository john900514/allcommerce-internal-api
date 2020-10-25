<?php

namespace App\Projectors\Customers;

use App\Aggregates\Customers\CustomerActivity;
use App\Emails;
use App\Events\Orders\OrderPaymentAuthorized;
use App\Events\Orders\OrderPaymentCaptured;
use App\Models\Customers\Customer;
use App\Models\PaymentGateways\PaymentProviders;
use App\Phones;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

class CustomersProjector extends Projector
{
    public function onOrderPaymentCaptured(OrderPaymentCaptured $event)
    {
        // Determine the platform of the order.
        $platform = $event->getTransaction()->misc['platform'];

        // Use the transaction to get the order record
        $order = $event->getTransaction()->order()
            ->with('billing_address')
            ->first();

        if(!is_null($order))
        {
            //Use the order record to get the billing address
            $billing = $order->billing_address;

            // Locate the customer via email or phone or skip.
            $customer = Customer::wherePhone($billing->phone)
                ->whereFirstName($billing->first_name)
                ->whereLastName($billing->last_name)
                ->first();

            if(is_null($customer))
            {
               // If not found, create
               $phone_model = Phones::firstOrCreate(['phone' => $billing->phone]);
               $cust_args = [
                   'first_name' => $billing->first_name,
                   'last_name' => $billing->last_name,
                   //'email' => $billing->email,
                   'phone' => $billing->phone,
                   'phone_uuid' => $phone_model->id
               ];

               $custard = new Customer();
               if($customer = $custard->insertNew($cust_args))
               {
                   // Call upon $aggy the CustomerActivity aggregate.
                   $aggy = CustomerActivity::retrieve($customer->id)
                       ->addCustomerRecord($customer);
               }
            }
            else
            {
                // Call upon $aggy the CustomerActivity aggregate.
                $aggy = CustomerActivity::retrieve($customer->id);
            }

            if(!is_null($customer))
            {
                // // array of email UUIDs (scope to active-shop's client)
                $email_record = Emails::whereEmail($billing->email)
                    ->whereShopUuid($order->shop_uuid)
                    ->first();

                if(!is_null($email_record))
                {
                    $aggy = $aggy->addEmailRecord($email_record);
                }

                $gateway_id = $event->getTransaction()->misc['enabled_gateway']['payment_provider']['id'];
                $gateway = PaymentProviders::find($gateway_id);
                $rebill_token = $event->getTransaction()->misc['details'];

                // array of orders (scope to active-shop)
                // - Payment Gateway Rebill Token with Associated Gateway
                $aggy = $aggy->addOrderRecord($order)
                    ->addRebillToken($gateway, $rebill_token)
                    ->persist();
            }
        }
    }
}
