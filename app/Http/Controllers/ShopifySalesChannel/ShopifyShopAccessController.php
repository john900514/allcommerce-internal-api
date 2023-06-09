<?php

namespace App\Http\Controllers\ShopifySalesChannel;

use App\Merchants;
use App\CheckoutFunnels;
use App\Services\Shopify\ShopifyAdminAPIService;
use App\ShopifyInstalls;
use Illuminate\Http\Request;
use Ixudra\Curl\Facades\Curl;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ShopifyShopAccessController extends Controller
{
    protected $install_status, $request, $funnels;

    public function __construct(Request $request, ShopifyInstalls $installs, CheckoutFunnels $funnels)
    {
        $this->funnels = $funnels;
        $this->request = $request;
        $this->install_status = $installs;
    }

    public function get_basic_store_info()
    {
        $results = ['success' => false, 'reason' => 'Shop not found!'];

        $data = $this->request->all();

        $validated = Validator::make($data, [
            'hmac' => 'bail|required',
            'shop' => 'bail|required|exists:App\ShopifyInstalls,shopify_store_url',
            'timestamp' => 'bail|required',
            'session' => 'bail|required',
            'locale' => 'bail|required',
        ]);

        if ($validated->fails())
        {
            foreach($validated->errors()->toArray() as $col => $msg)
            {
                $results['reason'] = $msg[0];
                break;
            }
        }
        else
        {
            // Get the install info or fail
            $status = $this->install_status->whereShopifyStoreUrl($data['shop'])->first();

            if(!is_null($status))
            {
                $install_info = $status->toArray();
                //unset($install_info['id']);
                unset($install_info['nonce']);
                unset($install_info['auth_code']);
                unset($install_info['deleted_at']);

                $response = [
                    'url' => $data['shop'],
                    'status' => $status->toArray(),
                    'allcommerce_shop' => []
                ];

                // If merchant is linked, send merchant info or []
                $shop = $status->shop()->with('merchant')
                    ->first();

                if(!is_null($shop))
                {
                    $response['allcommerce_shop'] = $shop->toArray();
                    $response['allcommerce_merchant'] = $shop->merchant->toArray();
                }

                if($funnel = $this->funnels->getDefaultFunnelByShop('shopify', $install_info['id']))
                {
                    $fun = [
                        'name' => $funnel->funnel_name,
                        'url' => 'https://'.$data['shop'].env('SHOPIFY_PROXY_URI', '/a/sales').'/secure/checkout/'.$funnel->id
                    ];

                    $response['funnel'] = $fun;
                }

                // @todo - ping Shopify for even more datas.


                $results = ['success' => true, 'shop' => $response];

            }
        }

        return response()->json($results);
    }

    public function get_store_shipping_rates(ShopifyInstalls $installs, ShopifyAdminAPIService $service)
    {
        $results = ['success' => false, 'reason' => 'Shop not found!'];

        $data = $this->request->all();

        $validated = Validator::make($data, [
            'shop_url' => 'bail|required|exists:App\ShopifyInstalls,shopify_store_url',
        ]);

        if ($validated->fails())
        {
            foreach($validated->errors()->toArray() as $col => $msg)
            {
                $results['reason'] = $msg[0];
                break;
            }
        }
        else
        {
            $install = $installs->whereShopifyStoreUrl($data['shop_url'])->first();

            if($zones = $service->getShippingZones($install))
            {
                if(array_key_exists('shipping_zones', $zones))
                {
                    $rates = $zones['shipping_zones'][0];
                    $res = [
                        'priceBased' => $rates['price_based_shipping_rates'],
                        'weightBased' => $rates['weight_based_shipping_rates']
                    ];
                    // curate the response if needed and return
                    $results = ['success' => true, 'shipping_rates' => $res];
                }
                else
                {
                    // curate and send back the Shopify fail message
                    $results['reason'] = 'Response from Shopify - '.$zones['error'];
                }
            }
            else
            {
                $results['reason'] = 'Could not contact Shopify!';
            }
        }

        return response()->json($results);
    }

    public function get_assigned_merchant_info(Merchants $merchants)
    {
        $results = ['success' => false, 'reason' => 'No Merchant Assigned!'];

        $data = $this->request->all();

        $validated = Validator::make($data, [
            'hmac' => 'bail|required',
            'shop' => 'bail|required',
            'timestamp' => 'bail|required',
            'session' => 'bail|required',
            'locale' => 'bail|required',
        ]);

        if ($validated->fails())
        {
            foreach($validated->errors()->toArray() as $col => $msg)
            {
                $results['reason'] = $msg[0];
                break;
            }
        }
        else
        {
            $install = $this->install_status->whereShopifyStoreUrl($data['shop'])
                ->with('allcommerce_merchant')
                ->first();

            if(!is_null($install))
            {
                if(!is_null($install->allcommerce_merchant))
                {
                    $results = ['success' => true, 'merchant' => $install->allcommerce_merchant->toArray()];
                }
            }
            else
            {
                $results['reason'] = 'Invalid Shop!';
            }
        }

        return response()->json($results);
    }
}
