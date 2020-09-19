<?php

namespace App\Services\Shopify;

use App\ShopifyInstalls;
use App\Services\AnAPIService;

class ShopifyAdminAPIService extends AnAPIService
{
    public function __construct()
    {

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
