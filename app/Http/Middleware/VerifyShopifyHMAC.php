<?php

namespace App\Http\Middleware;

use Closure;

class VerifyShopifyHMAC
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
        // @todo - verify HMAC is valid or Fail.

        return $next($request);
    }
}
