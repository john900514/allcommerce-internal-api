<?php

namespace App\Http\Controllers\ShopifySalesChannel;

use App\Jobs\Shopify\Inventory\ImportProductListings;
use App\Shops;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;
use App\ShopifyInstalls;
use Illuminate\Http\Request;
use Ixudra\Curl\Facades\Curl;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ShopifySalesChannelInstallerController extends Controller
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function nonce(ShopifyInstalls $installs, Shops $shops)
    {
        $results = ['success' => false, 'reason' => 'Missing Shop.'];

        $data = $this->request->all();

        // Validate for shop_url or fail.
        if(array_key_exists('shop_url', $data))
        {
            // Check shopify installs for shop url.
            $record = $installs->getByShopUrl($data['shop_url']);

            if($record)
            {
                // if exists, set a new nonce value with success.
                $record->nonce = Uuid::uuid4();
                $record->save();

                $nonce = $record->nonce;
            }
            else
            {
                // If not exists, create record or fail.
                $record = $installs->insertNonceRecord($data['shop_url']);
                if($record)
                {
                    $nonce = $record->nonce;
                }
                else
                {
                    $nonce = false;
                }
            }

            // send back just the nonce value with the success
            if($nonce)
            {
                $results = ['success'=> true, 'nonce' => $nonce];
            }
            else
            {
                $results['reason'] = 'Could not generate nonce';
            }
        }

        return response()->json($results);
    }

    public function confirm(ShopifyInstalls $installs)
    {
        $results = ['success' => false];

        $data = $this->request->all();

        $validated = Validator::make($data, [
            'hmac' => 'bail|required',
            'shop' => 'bail|required|exists:App\ShopifyInstalls,shopify_store_url',
            'timestamp' => 'bail|required',
            'code' => 'bail|required',
            'state' => 'bail|required|exists:App\ShopifyInstalls,nonce',
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
            $install_model = $installs->whereNonce($data['state'])
                ->first();

            $install_model->addOwnership();

            $install_model->auth_code = $data['code'];

            $payload = [
                'client_id' => env('SHOPIFY_SALES_CHANNEL_API_KEY'),
                'client_secret' => env('SHOPIFY_SALES_CHANNEL_SECRET'),
                'code' => $data['code']
            ];

            $response  = Curl::to('https://'.$data['shop'].'/admin/oauth/access_token')
                ->withData($payload)
                ->asJson(true)
                ->post();

            Log::info("Response from {$data['shop']} - ", [$response]);

            if((!is_null($response)) && array_key_exists('access_token', $response))
            {
                $install_model->access_token = $response['access_token'];
                $install_model->scopes = $response['scope'];
                $install_model->installed = 1;
                $install_model->save();

                $stats = $install_model->toArray();
                unset($stats['id']);
                unset($stats['deleted_at']);

                // queue the inventory import job
                ImportProductListings::dispatch($install_model)->onQueue('aco-'.env('APP_ENV').'-shopify');

                $results = ['success' => true, 'stats' => $stats];
            }
            else
            {
                $results['reason'] = 'Could not communicate with Shopify';
            }

        }

        return response($results);
    }

}
