<?php

namespace App\Http\Controllers\ShopifySalesChannel;

use App\ShopifyInstalls;
use Illuminate\Http\Request;
use Ixudra\Curl\Facades\Curl;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ShopifyShopAccessController extends Controller
{
    protected $install_status, $request;

    public function __construct(Request $request, ShopifyInstalls $installs)
    {
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
                unset($install_info['id']);
                unset($install_info['nonce']);
                unset($install_info['nonce']);
                unset($install_info['auth_code']);
                unset($install_info['deleted_at']);

                $response = [
                    'url' => $data['shop'],
                    'status' => $status->toArray(),
                    'allcommerce_merchant' => []
                ];

                // If merchant is linked, send merchant info or []
                $merchant = $status->allcommerce_merchant()->first();

                if(!is_null($merchant))
                {
                    $response['allcommerce_merchant'] = $merchant->toArray();
                }

                // @todo - ping Shopify for even more datas.

                $results = ['success' => true, 'shop' => $response];

            }
        }

        return response()->json($results);
    }
}
