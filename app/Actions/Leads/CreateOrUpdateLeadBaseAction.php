<?php

namespace App\Actions\Leads;

use App\Leads;
use App\Actions\Action;
use App\CheckoutFunnels;

class CreateOrUpdateLeadBaseAction implements Action
{
    protected $event_aggregate;

    public function __construct()
    {

    }

    public function execute($payload = null)
    {

    }

    protected function getCheckoutTypeDetails($checkoutType, $checkoutId)
    {
        $results = false;

        switch($checkoutType)
        {
            case 'checkout_funnel':
                $funnel = CheckoutFunnels::find($checkoutId);

                if(!is_null($funnel))
                {
                    $shop = $funnel->shop()->first();

                    if(!is_null($shop))
                    {
                        $products = $funnel->getProducts();

                        $results = [
                            'shop_id' => $shop->id,
                            'merchant_id' => $shop->merchant_id,
                            'client_id' => $shop->client_id,
                            'products' => $products
                        ];
                    }
                }
                break;
        }

        return $results;
    }

    protected function runPostProcessing(array $payload, Leads $lead, array $checkout_details) : void
    {
        $this->processEmailList($payload, $lead);
        $this->processShopifyDraftOrderIfQualified($lead, $checkout_details);
    }

    private function processEmailList($payload, Leads $lead) : void
    {
        // if attributes.emailList exists, add an attribute record and set it
        $attrs = $payload['attributes'];

        if(array_key_exists('emailList', $attrs))
        {
            $email_list_attr = $lead->attributes()->whereName('emailList')
                ->whereActive(1)->first();

            if(!is_null($email_list_attr))
            {
                $email_list_attr->value = $attrs['emailList'];
                $email_list_attr->active = 1;
            }
            else
            {
                $email_list_attr = new $this->lead_attrs;
                $email_list_attr->lead_uuid = $lead->id;
                $email_list_attr->name = 'emailList';
                $email_list_attr->value = $attrs['emailList'];
                $email_list_attr->misc = [];
                $email_list_attr->active = 1;

                $email_list_attr->shop_uuid = $lead->shop_uuid;
                $email_list_attr->merchant_uuid = $lead->merchant_uuid;
                $email_list_attr->client_uuid = $lead->client_uuid;
            }

            $email_list_attr->save();
        }
    }

    private function processShopifyDraftOrderIfQualified(Leads $lead, array $checkout_details) : void
    {
        $shop = $lead->shop()->first();

        // Check the included shop's shop type or skip
        if(!is_null($shop))
        {
            $shop_type_record = $shop->shop_type()->first();

            // Should be shopify or skip.
            if((!is_null($shop_type_record)) && ($shop_type_record->name == 'Shopify'))
            {
                if(!is_null($lead->email))
                {
                    $this->event_aggregate = $this->event_aggregate->addEmailAddress($lead->email);
                }

                if(!is_null($billing = $lead->billing_address()->first()))
                {
                    $this->event_aggregate = $this->event_aggregate->addBillingAddress($billing);
                }

                if(!is_null($shipping = $lead->shipping_address()->first()))
                {
                    $this->event_aggregate = $this->event_aggregate->addShippingAddress($shipping);
                }

                $this->event_aggregate->createOrUpdateDraftOrder();
            }
        }
    }
}
