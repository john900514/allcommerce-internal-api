<?php

namespace App\Http\Controllers;

use App\Services\Shopify\ShopifyAdminAPIService;
use Illuminate\Http\Request;

class ShopResourceController extends Controller
{
    protected $request, $shopify;

    public function __construct(Request $request, ShopifyAdminAPIService $shopify)
    {
        $this->request = $request;
        $this->shopify = $shopify;
    }

    public function index()
    {
        $results = ['success' => false, 'reason' => 'Shop Not Found'];
        $code = 500;

        if(session()->has('active_shop'))
        {
            $shop = session()->get('active_shop');

            $results = $shop->toArray();
            $results['merchant'] = $shop->merchant()->first()->name;
            $results['client'] = $shop->client()->first()->name;
            $results['shop_type'] = $shop->shoptype()->first()->name;
            unset($results['merchant_id']);
            unset($results['client_id']);
            unset($results['deleted_at']);
            unset($results['active']);
            $code = 200;
        }

        return response($results, $code);
    }

    public function shipping_methods()
    {
        $results = ['success' => false, 'reason' => 'Shop Not Found'];
        $code = 500;

        if(session()->has('active_shop'))
        {
            $shop = session()->get('active_shop');
            $shop_type = $shop->shop_type()->first();

            if(!is_null($shop_type))
            {
                $shop_type = $shop_type->name;

                // Determine the shop to be a shopify shop or fail, not supported yet
                switch($shop_type)
                {
                    // If shopify shop, use the ShopifyAdminAPIService to get the shipping zones (since this is a shopify public app) or fail
                    case 'Shopify':
                        if(array_key_exists('shipping_zones', $zones = $this->shopify->getShippingZones($shop->shopify_install()->first())) > 0)
                        {
                            // If zones retrieved, parse into a curated array and return
                            $parsed_zones = $this->shopify->parseShippingZones($zones['shipping_zones']);

                            if(count($parsed_zones) > 0)
                            {
                                $results = ['success' => true, 'shipping_rates' => $parsed_zones];
                            }
                            else
                            {
                                $results['reason'] = 'Error - Shop has no available Shipping Zones.';
                            }
                        }
                        else
                        {
                            $results['reason'] = 'Error - Shopify could not be reached';
                        }
                        break;

                    // @todo - make sure other platforms return similar structure shipping method arrays

                    case 'Web Only':
                    default:
                        $results['reason'] = 'Unsupported Shop type.';
                }
            }
        }

        return response($results, $code);
    }
}
