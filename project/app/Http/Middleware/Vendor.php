<?php

namespace App\Http\Middleware;
use Closure;

class Vendor
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
        if (auth()->user()->IsVendor()) {
            return $next($request);
        }
        return response()->json(['status' => false, 'data' => [], 'error' => ["message" => 'You don\'t have the permission to access this section.']]);
    }
}
