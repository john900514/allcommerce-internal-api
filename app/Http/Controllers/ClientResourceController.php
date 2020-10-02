<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ClientResourceController extends Controller
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function index()
    {
        $results = ['success' => false, 'reason' => 'Client Not Found'];
        $code = 500;

        if(!is_null($client = session()->get('active_client')))
        {
            $results = $client->toArray();
            unset($results['active']);
            unset($results['deleted_at']);

            if(session()->has('active_merchant'))
            {
                $merchant = session()->get('active_merchant');
                $results['merchants'] = [
                    [
                        'id' => $merchant->id,
                        'name' => $merchant->name,
                    ]
                ];

                $shops = $merchant->shops()->get(['id', 'merchant_id', 'name']);
                $results['shops'] = $shops->toArray();

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

            $code = 200;
        }

        return response($results, $code);
    }
}
