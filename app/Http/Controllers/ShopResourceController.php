<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ShopResourceController extends Controller
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
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
        }

        return response($results, $code);
    }
}
