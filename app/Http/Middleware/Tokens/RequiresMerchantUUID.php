<?php

namespace App\Http\Middleware\Tokens;

use Closure;
use App\Clients;
use App\Merchants;
use App\MerchantApiTokens;
use Illuminate\Support\Facades\Validator;

class RequiresMerchantUUID
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
            'x-ac-merchant-uuid.0'  => 'sometimes|required|exists:merchants,id',
            'x-ac-shop-uuid.0'  => 'sometimes|required|exists:shops,id'
        ]);

        if($validated->fails())
        {
            $results = ['success' => false, 'reason' => 'Error'];
            foreach($validated->errors()->toArray() as $col => $msg)
            {
                $results['reason'] = $msg[0];
                break;
            }

            return response($results, 500);
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
                    $merchant = $token->merchant()->first();

                    if(!is_null($merchant))
                    {
                        // Make sure the merchant's client and the token's client match
                        if($merchant->client_id == $token->client_id)
                        {
                            session()->put('active_merchant', $merchant);

                            if(array_key_exists('x-ac-shop-uuid', $headers))
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
                            $results = ['success' => false, 'reason' => 'Client Mismatch'];
                            return response($results, 409);
                        }
                    }
                    else
                    {
                        $results = ['success' => false, 'reason' => 'Invalid Merchant'];
                        return response($results, 401);
                    }
                }
                elseif($token->token_type == 'shop')
                {
                    $shop = $token->shop()->first();

                    if(!is_null($shop))
                    {
                        if($shop->client_id == $token->client_id)
                        {
                            $merchant = $shop->merchant()->first();
                            session()->put('active_merchant', $merchant);
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
                    $client = $token->client;

                    // if host client, check header for (merchant or shop) or fail
                    if($client->id == Clients::getHostClient())
                    {
                        if(array_key_exists('x-ac-merchant-uuid', $headers))
                        {
                            $merchant = Merchants::find($headers['x-ac-merchant-uuid'][0]);

                            if(!is_null($merchant))
                            {
                                session()->put('active_merchant', $merchant);
                            }
                            else
                            {
                                $results['reason'] = 'Invalid Merchant UUID';
                                return response($results, 401);
                            }
                        }
                        else
                        {
                            $results['reason'] = 'Missing Merchant UUID';
                            return response($results, 412);
                        }
                    }
                    else
                    {
                        if(array_key_exists('x-ac-merchant-uuid', $headers))
                        {
                            $merchant = $client->merchants()->whereId($headers['x-ac-merchant-uuid'][0])->first();

                            if(!is_null($merchant))
                            {
                                session()->put('active_merchant', $merchant);
                            }
                            else
                            {
                                $results['reason'] = 'Invalid Merchant UUID';
                                return response($results, 401);
                            }
                        }
                        else
                        {
                            $results['reason'] = 'Missing Merchant UUID';
                            return response($results, 412);
                        }

                    }

                    if(array_key_exists('x-ac-shop-uuid', $headers))
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
