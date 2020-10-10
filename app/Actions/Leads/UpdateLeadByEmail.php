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
                $aggy = ShopifyOrderAggregate::retrieve($lead->id)
                    ->addEmailAddress($payload['email'])
                    ->addContactOptin($payload['emailList']);

                $results = ['lead' => $aggy->getLead()->toArray()];
                $results['lead']['email'] = $payload['email'];

                $shipping = $lead->shipping_address()->first();
                $billing  =  $lead->billing_address()->first();

                if(!is_null($shipping))
                {
                    $results['shipping'] = $shipping->toArray();
                    $results['shipping']['email'] = $payload['email'];

                    $aggy = $aggy->linkEmailToShipping($payload['email']);
                }

                if(!is_null($billing))
                {
                    $results['billing'] = $billing->toArray();
                    $results['billing']['email'] = $payload['email'];

                    $aggy = $aggy->linkEmailToBilling($payload['email']);
                }

                $aggy->persist();
            }
        }

        return $results;
    }
}
