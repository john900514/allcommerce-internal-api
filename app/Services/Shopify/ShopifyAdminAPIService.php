<?php

namespace App\Services\Shopify;

use App\ShopifyInstalls;
use App\Services\AnAPIService;

class ShopifyAdminAPIService extends AnAPIService
{
    public function __construct() {}

    public function getShippingZones(ShopifyInstalls $install)
    {
        $headers = [
            'X-Shopify-Access-Token: '.$install->access_token
        ];

        $api_url = 'https://'.$install->shopify_store_url.'/admin/api/2020-07/shipping_zones.json';

        return $this->get($api_url, null, $headers);
    }

    public function parseShippingZones(array $zones)
    {
        $results = [];

        // Currently, we are only supporting price-based shipping rates

        foreach($zones as $idx => $zone)
        {
            foreach($zone['price_based_shipping_rates'] as $idx => $rate)
            {
                $results[] = [
                    //'handle' => 'shopify-'.rawurlencode($zone['name'])."-{$zone['price']}",
                    'price'      => $rate['price'],
                    'title'      => $rate['name'],
                    'custom'     => true,
                    'min_price'  => $rate['min_order_subtotal'],
                    'max_price'  => $rate['max_order_subtotal'],
                    'max_weight' => 0.00,
                    'min_weight' => 0.00,
                ];
            }
        }

        // @todo - support weight based shipping rates

        return $results;
    }

    public function getCustomer(ShopifyInstalls $install, $query)
    {
        $headers = [
            'X-Shopify-Access-Token: '.$install->access_token
        ];

        $api_url = 'https://'.$install->shopify_store_url.'/admin/api/2020-07/customers/search.json?';
        $url = $api_url.$query;

        return $this->get($url, null, $headers);
    }

    public function postCustomer(ShopifyInstalls $install, $payload)
    {
        $headers = [
            'X-Shopify-Access-Token: '.$install->access_token
        ];

        $api_url = 'https://'.$install->shopify_store_url.'/admin/api/2020-07/customers.json?';

        return $this->post($api_url, $payload, $headers);
    }

    public function updateCustomer(ShopifyInstalls $install, $payload)
    {
        $headers = [
            'X-Shopify-Access-Token: '.$install->access_token
        ];

        $api_url = 'https://'.$install->shopify_store_url.'/admin/api/2020-07/customers/'.$payload['customer']['id'].'.json?';

        return $this->put($api_url, $payload, $headers);
    }
}
