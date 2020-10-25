<?php

namespace App\Services\Shopify;

use App\Models\Sales\Transactions;
use App\ShopifyInstalls;

class ShopifyOrderService extends ShopifyAdminAPIService
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getOrder(ShopifyInstalls $install, $order_id)
    {
        $headers = [
            'X-Shopify-Access-Token: '.$install->access_token
        ];

        $api_url = 'https://'.$install->shopify_store_url.'/admin/api/2020-10/orders/'.$order_id.'.json';

        return $this->get($api_url, [], $headers);
    }

    public function getTransactions(ShopifyInstalls $install, $order_id)
    {
        $headers = [
            'X-Shopify-Access-Token: '.$install->access_token
        ];

        $api_url = 'https://'.$install->shopify_store_url.'/admin/api/2020-10/orders/'.$order_id.'/transactions.json';

        return $this->get($api_url, [], $headers);
    }

    public function postPaidTransaction(ShopifyInstalls $install, $final_total, $order_id)
    {
        $headers = [
            'X-Shopify-Access-Token: '.$install->access_token
        ];

        $api_url = 'https://'.$install->shopify_store_url.'/admin/api/2020-10/orders/'.$order_id.'/transactions.json';


        $payload = [
            'transaction' => [
                'currency' => 'USD',
                'amount'  => floatval($final_total),
                'kind' => 'capture',
                //'parent_id' => intval($auth_capt_id)
            ]
        ];

        return $this->post($api_url, $payload, $headers);
    }

    public function closeOrder(ShopifyInstalls $install, $order_id)
    {
        $headers = [
            'X-Shopify-Access-Token: '.$install->access_token
        ];

        $api_url = 'https://'.$install->shopify_store_url.'/admin/api/2020-10/orders/'.$order_id.'/close.json';


        $payload = [];

        return $this->post($api_url, $payload, $headers);
    }
}
