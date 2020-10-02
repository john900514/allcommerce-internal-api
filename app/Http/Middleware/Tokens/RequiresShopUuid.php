<?php

namespace App\Http\Middleware\Tokens;

use Closure;
use App\Shops;
use App\Clients;
use App\MerchantApiTokens;
use Illuminate\Support\Facades\Validator;

class RequiresShopUuid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $headers = $request->headers->all();

        $validated = Validator::make($headers, [
            'x-allcommerce-token.0' => 'bail|required|exists:merchant_api_tokens,token',
            'x-ac-shop-uuid.0'  => ' sometimes|required|exists:shops,id'
        ]);

        if($validated->fails())
        {
            if ($validated->fails())
            {
                $results = ['success' => false, 'reason' => 'Error'];
                foreach($validated->errors()->toArray() as $col => $msg)
                {
                    $results['reason'] = $msg[0];
                    break;
                }

                return response($results, 500);
            }
        }
        else
        {
            $token = MerchantApiTokens::whereToken($headers['x-allcommerce-token'])
                ->with('client')
                ->first();

            if($token->active)
            {
                if($token->token_type == 'merchant')
                {
                    if(array_key_exists('x-ac-shop-uuid', $headers))
                    {
                        $merchant = $token->merchant()->first();

                        if(!is_null($merchant))
                        {
                            if($merchant->client_id == $token->client_id)
                            {
                                $shop = $merchant->shops()->whereId($headers['x-ac-shop-uuid'][0])->first();

                                if(!is_null($shop))
                                {
                                    session()->put('active_shop', $shop);
                                }
                                else
                                {
                                    $results['reason'] = 'Invalid Shop UUID';
                                    return response($results, 401);
                                }
                            }
                        }
                        else
                        {
                            $results = ['success' => false, 'reason' => 'Invalid Merchant'];
                            return response($results, 401);
                        }
                    }
                    else
                    {
                        $results['reason'] = 'Missing Shop UUID';
                        return response($results, 412);
                    }
                }
                elseif($token->token_type == 'shop')
                {
                    $shop = $token->shop()->first();

                    if(!is_null($shop))
                    {
                        if($shop->client_id == $token->client_id)
                        {
                            session()->put('active_shop', $shop);
                        }
                        else
                        {
                            $results = ['success' => false, 'reason' => 'Client Mismatch'];
                            return response($results, 409);
                        }
                    }
                    else
                    {
                        $results = ['success' => false, 'reason' => 'Invalid Shop'];
                        return response($results, 401);
                    }
                }
                else
                {
                    if(array_key_exists('x-ac-shop-uuid', $headers))
                    {
                        $client = $token->client;
                        // if host client, check header for (merchant or shop) or fail
                        if($client->id == Clients::getHostClient())
                        {
                            $shop = Shops::find($headers['x-ac-shop-uuid'][0]);

                            if(!is_null($shop))
                            {
                                session()->put('active_shop', $shop);
                            }
                            else
                            {
                                $results['reason'] = 'Invalid Shop UUID';
                                return response($results, 401);
                            }
                        }
                        else
                        {
                            $client = $token->client;
                            $shop = $client->shops()->whereId($headers['x-ac-shop-uuid'][0])->first();

                            if(!is_null($shop))
                            {
                                session()->put('active_shop', $shop);
                            }
                            else
                            {
                                $results['reason'] = 'Invalid Shop UUID';
                                return response($results, 401);
                            }
                        }
                    }
                    else
                    {
                        $results['reason'] = 'Missing Shop UUID';
                        return response($results, 412);
                    }
                }
            }
            else
            {
                $results = ['success' => false, 'reason' => 'Token inactive'];
                return response($results, 401);
            }
        }

        return $next($request);
    }
}
