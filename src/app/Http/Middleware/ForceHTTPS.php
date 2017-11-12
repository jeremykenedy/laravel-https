<?php

namespace jeremykenedy\LaravelHttps\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceHTTPS
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!$request->secure()) {

            // Force Request URI to HTTPS
            return redirect()->secure($request->getRequestUri());
        }

        return $next($request);
    }
}
