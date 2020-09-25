<?php

namespace App\Actions\Leads;

use App\Aggregates\Orders\ShopifyOrderAggregate;
use App\Leads;
use App\Emails;
use App\LeadAttributes;
use App\CheckoutFunnels;

class UpdateLeadByEmail extends CreateOrUpdateLeadBaseAction
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
        $results = false;

        if(!is_null($payload))
        {
            $lead = $this->leads->find($payload['lead_uuid']);

            if(!is_null($lead))
            {
                $lead->email = $payload['email'];

                if($lead->save())
                {
                    $results = ['lead' => $lead->toArray()];

                    $aggy = ShopifyOrderAggregate::retrieve($lead->id)
                        ->addLeadRecord($lead, false)
                        ->addEmailAddress($lead->email)
                        ->addContactOptin($payload['emailList']);

                    $shipping = $lead->shipping_address()->first();
                    $billing  =  $lead->billing_address()->first();

                    if(!is_null($shipping))
                    {
                        $results['shipping'] = $shipping->toArray();
                        $aggy = $aggy->addShippingAddress($shipping, false);
                    }

                    if(!is_null($billing))
                    {
                        $results['billing'] = $billing->toArray();
                        $aggy = $aggy->addBillingAddress($billing, false);
                    }

                    $aggy->linkEmailToBilling($lead->email)
                        ->linkEmailToShipping($lead->email)
                        ->persist();
                }
            }
        }

        return $results;
    }
}
