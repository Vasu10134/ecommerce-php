<?php

namespace App\Http\Middleware;
use Closure;
use App\Models\Generalsetting;

class HTTPSConnection {

    public function handle($request, Closure $next)
    {
        try {
            $gs = Generalsetting::find(1);
            
            if($gs && $gs->is_secure == 1) {
                if (!$request->secure()) {
                    return redirect()->secure($request->getRequestUri());
                }
            }
        } catch (\Exception $e) {
            // If there's an error with the database or Generalsetting model,
            // just continue without HTTPS enforcement
            \Log::warning('HTTPS middleware error: ' . $e->getMessage());
        }

        return $next($request);
    }

}



?>