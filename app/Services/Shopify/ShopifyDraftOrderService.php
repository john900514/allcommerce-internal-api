<?php

namespace App\Services\Shopify;

use App\InventoryVariants;
use App\ShopifyInstalls;

class ShopifyDraftOrderService extends ShopifyAdminAPIService
{
    public function __construct()
    {
        parent::__construct();
    }

    public function prepAddress($address)
    {
        $results = [
            'address1' => $address->address,
            'address2' => (!is_null($address->address2)) ? $address->address2 : ((!is_null($address->apt)) ? $address->apt : ''),
            'city' => $address->city,
            'country' => strtoupper($address->country),
            'first_name' => $address->first_name,
            'last_name' => $address->last_name,
            'name' => "{$address->first_name} $address->last_name",
            'phone' => $address->phone,
            'province' => $address->state,
            'zip' => $address->zip
        ];

        return $results;
    }

    public function prepProducts(InventoryVariants $variants, array $checkout_details)
    {
        $results = [];

        if(count($checkout_details['products']) > 0)
        {
            foreach ($checkout_details['products'] as $line_item)
            {
                $product_variant = $variants->find($line_item['variant']);

                if(!is_null($product_variant))
                {
                    $results[] = [
                        'variant_id' => $product_variant->inventory_item_id,
                        'product_id' => $product_variant->inventory_id,
                        'quantity' => $line_item['qty'],
                        'sku' => $product_variant->sku,
                        'taxable' => true
                    ];
                }
            }
        }

        return $results;
    }

    public function postDraftOrder(ShopifyInstalls $install, $payload)
    {
        $headers = [
            'X-Shopify-Access-Token: '.$install->access_token
        ];

        $api_url = 'https://'.$install->shopify_store_url.'/admin/api/2020-07/draft_orders.json';

        return $this->post($api_url, $payload, $headers);
    }

    public function updateDraftOrder(ShopifyInstalls $install, $payload)
    {
        $headers = [
            'X-Shopify-Access-Token: '.$install->access_token
        ];

        $api_url = 'https://'.$install->shopify_store_url.'/admin/api/2020-07/draft_orders/'.$payload['draft_order']['id'].'.json';

        return $this->put($api_url, $payload, $headers);
    }
}
