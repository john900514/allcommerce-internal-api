<?php

namespace App\Http\Middleware\Tokens;

use App\MerchantApiTokens;
use Closure;
use Illuminate\Support\Facades\Validator;

class ValidateAPIToken
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
            'x-allcommerce-token' => 'bail|required|exists:merchant_api_tokens,token'
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
                session()->put('active_client', $token->client);

                // Further eval the token
                if($token->token_type == 'merchant')
                {
                    // if a merchant token - set the merchant in the session
                    $merchant = $token->merchant()->first();
                    session()->put('active_merchant', $merchant);
                }
                elseif($token->token_type == 'shop')
                {
                    // if a shop token - set the shop in the session
                    $shop = $token->shop()->first();
                    session()->put('active_shop', $shop);
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
