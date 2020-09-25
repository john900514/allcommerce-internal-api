<?php

namespace App\Actions\Leads;

use App\Aggregates\Orders\ShopifyOrderAggregate;
use App\Leads;
use App\Emails;
use App\LeadAttributes;

class CreateLeadByEmail extends CreateOrUpdateLeadBaseAction
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
            $checkoutId = $payload['checkoutId'];
            $checkoutType = $payload['checkoutType'];

            // Get the checkout details or fail
            if($checkout_details = $this->getCheckoutTypeDetails($checkoutType, $checkoutId))
            {
                $lead = $this->leads;
                $lead->reference_type = $checkoutType;
                $lead->reference_uuid = $checkoutId;

                $lead->email = $payload['email'];

                $lead->shop_uuid = $checkout_details['shop_id'];
                $lead->merchant_uuid = $checkout_details['merchant_id'];
                $lead->client_uuid = $checkout_details['client_id'];

                // Create the lead with the email or fail
                if($lead->save())
                {
                    $results = $lead->toArray();

                    ShopifyOrderAggregate::retrieve($lead->id)
                        ->addLeadRecord($lead)
                        ->addLineItems($checkout_details['products'])
                        ->addEmailAddress($lead->email)
                        ->addContactOptin($payload['emailList'])
                        ->persist();
                }
            }
        }

        return $results;
    }


}
