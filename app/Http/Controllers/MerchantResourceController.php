<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MerchantResourceController extends Controller
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function index()
    {
        $results = ['success' => false, 'reason' => 'Merchant Not Found'];
        $code = 500;

        if(session()->has('active_client'))
        {
            $client = session()->get('active_client');

            if(session()->has('active_merchant'))
            {
                $merchant = session()->get('active_merchant');

                $results = $merchant->toArray();
                unset($results['active']);
                unset($results['deleted_at']);

                if(session()->has('active_shop'))
                {
                    $shop = session()->get('active_shop');
                    $shop_obj = [
                        'id' => $shop->id,
                        'name' => $shop->name
                    ];

                    $results['shops'] = [$shop_obj];
                }
                else
                {
                    $shops = $merchant->shops()->get(['id', 'merchant_id', 'name']);
                    $results['shops'] = $shops->toArray();
                }

                $code = 200;
            }
            elseif(session()->has('active_shop'))
            {
                $shop = session()->get('active_shop');

                $merchant = $shop->merchant()->first(['id', 'name']);
                $results['merchants'] = [$merchant->toArray()];
                $results['shops'] = [
                    [
                        'id' => $shop->id,
                        'name' => $shop->name,
                        'merchant_id' => $shop->merchant_id
                    ]
                ];

            }
            else
            {
                $merchants = $client->merchants()->get(['id', 'name']);
                $shops = $client->shops()->get(['id', 'merchant_id', 'name']);

                $results['merchants'] = $merchants->toArray();
                $results['shops'] = $shops->toArray();
            }
        }

        return response($results, $code);
    }
}
