<?php

namespace App\Actions\Leads;

use App\Actions\Action;
use App\CheckoutFunnels;
use App\Leads;

class CreateOrUpdateLeadBaseAction implements Action
{
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

    protected function runPostProcessing($payload, Leads $lead) : void
    {
        $this->processEmailList($payload, $lead);
        $this->processShopifyDraftOrderIfQualified($payload, $lead);
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

    protected function triggerPhoneTableCheck($email, $deets) : void
    {

    }

    protected function triggerEmailTableCheck($email, $deets) : void
    {

    }

    private function processShopifyDraftOrderIfQualified($payload, Leads $lead) : void
    {
        $shop = $lead->shop()->first();

        // Check the included shop's shop type or skip
        if(!is_null($shop))
        {
            $shop_type_record = $shop->shop_type()->first();

            // Should be shopify or skip.
            if((!is_null($shop_type_record)) && ($shop_type_record->name == 'Shopify'))
            {
                // @todo - fire job to ping shopify to draft the order and store in the DB as an attribute.
            }
        }
    }
}
