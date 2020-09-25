<?php

namespace App\Actions\Leads;

use App\Leads;
use App\BillingAddresses;
use App\Aggregates\Orders\ShopifyOrderAggregate;

class UpdateLeadByBillingAddress extends CreateOrUpdateLeadBaseAction
{
    protected $leads, $billing;

    public function __construct(Leads $leads, BillingAddresses $billing)
    {
        parent::__construct();
        $this->leads = $leads;
        $this->billing = $billing;
    }

    public function execute($payload = null)
    {
        $results = false;

        if(!is_null($payload))
        {
            $lead = $this->leads->find($payload['lead_uuid']);

            if(!is_null($lead))
            {
                $aggy = ShopifyOrderAggregate::retrieve($lead->id)
                    ->addLeadRecord($lead)
                    ->addContactOptin($payload['emailList']);

                $billing = null;
                if(array_key_exists('billing_uuid', $payload))
                {
                    // pull the Billing Addresses record;
                    $billing = $this->billing->find($payload['billing_uuid']);

                    if(!is_null($billing))
                    {
                        $aggy = $aggy->addBillingAddress($billing, false)
                            ->updateBillingAddress($payload['billing']);
                    }
                    else
                    {
                        if(array_key_exists('billing', $payload))
                        {
                            $payload['billing']['client_uuid'] = $lead->client_uuid;
                            $payload['billing']['merchant_uuid'] = $lead->merchant_uuid;
                            $payload['billing']['shop_uuid'] = $lead->shop_uuid;

                            // add the Billing Address
                            $billing = new $this->billing($payload['billing']);
                            $billing->save();
                            $aggy = $aggy->addBillingAddress($billing);
                        }

                    }
                }
                else
                {
                    if(array_key_exists('billing', $payload))
                    {
                        // add the Billing Address
                        $payload['billing']['client_uuid'] = $lead->client_uuid;
                        $payload['billing']['merchant_uuid'] = $lead->merchant_uuid;
                        $payload['billing']['shop_uuid'] = $lead->shop_uuid;

                        $billing = new $this->billing($payload['billing']);
                        $billing->save();
                        $aggy = $aggy->addBillingAddress($billing);
                    }
                }

                // link the email everywhere if its available!
                if(!is_null($lead->email))
                {
                    $aggy = $aggy->addEmailAddress($lead->email)
                        ->linkEmailToBilling($lead->email)
                    ;
                }

                if(!is_null($billing))
                {
                    $yes = true;
                    if($yes)
                    {
                        $aggy = $aggy->updateShopifyDraftOrder();
                    }
                }

                // Let's go ahead and make it all happen then we'll go about our business
                $aggy->persist();

                $results = [
                    'lead' => $lead,
                    'billing' => $billing
                ];
            }
        }

        return $results;
    }
}
