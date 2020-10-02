<?php

namespace App\Http\Controllers\Auth;

use App\Clients;
use App\MerchantApiTokens;
use Ramsey\Uuid\Uuid;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class FauxAuthenticationController extends Controller
{
    protected $clients, $request, $tokens;

    public function __construct(Request $request, MerchantApiTokens $tokens, Clients $clients)
    {
        $this->request = $request;
        $this->tokens = $tokens;
        $this->clients = $clients;
    }

    public function create()
    {
        $results = ['success' => false, 'reason' => 'Could Not Create Token'];
        $code = 500;

        $data = $this->request->all();

        $validated = Validator::make($data, [
            'client_id' => 'bail|required|exists:clients,id',
            'token_type' => 'bail|required|in:shop,client,merchant',
            'scopes' => 'bail|required_unless:token_type,client|array',
            'scopes.merchant_id' => 'bail|required_if:token_type,merchant|exists:merchants,id',
            'scopes.shop_id' => 'bail|required_if:token_type,shop|exists:shops,id'
        ]);

        if($validated->fails())
        {
            foreach($validated->errors()->toArray() as $col => $msg)
            {
                $results['reason'] = $msg[0];
                break;
            }
        }
        else
        {
            $new_token = Uuid::uuid4();

            switch($data['token_type'])
            {
                case 'client':
                    $data['scopes'] = null;
                    $data['token'] = $new_token;
                    $record = new $this->tokens();
                    foreach($data as $col => $val)
                    {
                        $record->$col = $val;
                    }

                    if($record->save())
                    {
                        $record = $record->whereToken($new_token)->first();
                        $results = $record->toArray();
                        $code = 200;
                    }
                    break;

                case 'merchant':
                    $client = $this->clients->find($data['client_id']);
                    $merchant = $client->merchants()->whereId($data['scopes']['merchant_id'])->first();

                    if(!is_null($merchant))
                    {
                        $data['scopes'] = [
                            'merchant_id' => $data['scopes']['merchant_id']
                        ];
                        $data['token'] = $new_token;
                        $record = new $this->tokens();
                        foreach($data as $col => $val)
                        {
                            $record->$col = $val;
                        }

                        if($record->save())
                        {
                            $record = $record->whereToken($new_token)->first();
                            $results = $record->toArray();
                            $code = 200;
                        }
                    }
                    else
                    {
                        $results['reason'] = 'Invalid Merchant';
                    }
                    break;

                case 'shop':
                    $client = $this->clients->find($data['client_id']);
                    $shop = $client->shops()->whereId($data['scopes']['shop_id'])->first();

                    if(!is_null($shop))
                    {
                        $data['scopes'] = [
                            'shop_id' => $data['scopes']['shop_id']
                        ];
                        $data['token'] = $new_token;
                        $record = new $this->tokens();
                        foreach($data as $col => $val)
                        {
                            $record->$col = $val;
                        }

                        if($record->save())
                        {
                            $record = $record->whereToken($new_token)->first();
                            $results = $record->toArray();
                            $code = 200;
                        }
                    }
                    else
                    {
                        $results['reason'] = 'Invalid Shop';
                    }
                    break;

                default:
                    $results['reason'] = 'Unsupported Token Type';
            }
        }

        return response($results, $code);
    }

    public function update()
    {
        $results = ['success' => false, 'reason' => 'Could Not Update Token'];
        $code = 500;

        $headers = $this->request->headers->all();

        $token = MerchantApiTokens::whereToken($headers['x-allcommerce-token'])->first();
        $token->token = Uuid::uuid4();

        if($token->save())
        {
            $results = $token->toArray();
            $code = 200;
        }

        return response($results, $code);
    }

    public function active()
    {
        $results = ['success' => false, 'reason' => 'Could Not Update Token'];
        $code = 500;

        $headers = $this->request->headers->all();
        $data = $this->request->all();

        $validated = Validator::make($data, [
            'active' => 'bail|required|boolean',
        ]);

        if($validated->fails())
        {
            foreach($validated->errors()->toArray() as $col => $msg)
            {
                $results['reason'] = $msg[0];
                break;
            }
        }
        else
        {
            $token = MerchantApiTokens::whereToken($headers['x-allcommerce-token'])->first();
            $token->active = $data['active'];

            if($token->save())
            {
                $results = $token->toArray();
                $code = 200;
            }
        }

        return response($results, $code);
    }

    public function delete()
    {
        $results = ['success' => false, 'reason' => 'Could Not Delete Token'];
        $code = 500;

        $headers = $this->request->headers->all();

        $token = MerchantApiTokens::whereToken($headers['x-allcommerce-token'])->first();

        if($token->delete())
        {
            $results = ['success' => true];
            $code = 200;
        }

        return response($results, $code);
    }
}
