<?php

namespace App\Http\Middleware;

use Closure;

class ApplyScopes
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
        $user = auth()->user();
        $merchant = $user->merchant();

        if(!is_null($merchant))
        {
            session()->put('scope_merchant', $merchant);
            return $next($request);
        }

        return response(['status' => 'fail', 'reason' => 'Invalid Merchant.']);
    }
}
