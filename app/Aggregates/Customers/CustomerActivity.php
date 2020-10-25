<?php

namespace App\Aggregates\Customers;

use App\Emails;
use App\Events\Customers\CustomerEmailLinked;
use App\Events\Customers\CustomerPurchasedOrderLinked;
use App\Events\Customers\CustomerRebillTokenAcquired;
use App\Events\Customers\NewCustomerCreated;
use App\Models\Customers\Customer;
use App\Models\PaymentGateways\PaymentProviders;
use App\Models\Sales\Orders;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;
use function GuzzleHttp\Psr7\str;

class CustomerActivity extends AggregateRoot
{
    protected $customer;
    protected $emails = [];
    protected $associated_shops = [];
    protected $first_shop_uuid, $first_client_uuid;
    protected $purchased_orders = [];
    protected $rebill_tokens = [];

    public function applyNewCustomerCreated(NewCustomerCreated $event)
    {
        $this->customer = $event->getCustomer();
    }

    public function applyCustomerEmailLinked(CustomerEmailLinked $event)
    {
        if(!array_key_exists($event->getEmail()->id, $this->emails))
        {
            $this->emails[$event->getEmail()->id]  = [
                'email' => $event->getEmail(),
                'dates' => [
                    date('Y-m-d h:i:s', strtotime('now'))
                ]
            ];
        }
        else
        {
            $this->emails[$event->getEmail()->id]['dates'][] = date('Y-m-d h:i:s', strtotime('now'));
        }
    }

    public function applyCustomerPurchasedOrderLinked(CustomerPurchasedOrderLinked $event)
    {
        $this->purchased_orders[] = $event->getOrder();
    }

    public function applyCustomerRebillTokenAcquired(CustomerRebillTokenAcquired $event)
    {
        if(!array_key_exists($event->getGateway()->id, $this->rebill_tokens))
        {
            $this->rebill_tokens[$event->getGateway()->id] = [
                'name' => $event->getGateway()->name,
                'tokens' => [$event->getToken()]
            ];
        }
        else
        {
            $this->rebill_tokens[$event->getGateway()->id]['tokens'][] = $event->getToken();
        }
    }

    public function addCustomerRecord(Customer $customer)
    {
        $this->customer = $customer;
        $this->recordThat(new NewCustomerCreated($customer));

        return $this;
    }

    public function addEmailRecord(Emails $email)
    {
        $this->recordThat(new CustomerEmailLinked($this->customer, $email));

        return $this;
    }

    public function addOrderRecord(Orders $order)
    {
        $this->recordThat(new CustomerPurchasedOrderLinked($this->customer, $order));

        return $this;
    }

    public function addRebillToken(PaymentProviders $gateway, array $token)
    {
        $this->recordThat(new CustomerRebillTokenAcquired($this->customer, $gateway, $token));

        return $this;
    }
}
