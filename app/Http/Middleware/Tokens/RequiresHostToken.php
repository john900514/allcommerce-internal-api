<?php

namespace App\Http\Middleware\Tokens;

use App\Clients;
use App\MerchantApiTokens;
use Closure;
use Illuminate\Support\Facades\Validator;

class RequiresHostToken
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
                if($token->token_type == 'client')
                {
                    $client = $token->client;

                    if($client->id != Clients::getHostClient())
                    {
                        $results['reason'] = 'Access Denied';
                        return response($results, 401);
                    }
                }
                else
                {
                    $results['reason'] = 'Invalid Scope';
                    return response($results, 401);
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
