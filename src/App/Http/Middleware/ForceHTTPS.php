<?php

namespace jeremykenedy\LaravelHttps\App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;

class ForceHTTPS
{
    public function handle($request, Closure $next)
    {
        if (! $request->secure()
            && config('LaravelHttps.ForceHttpsCheckEnvironment', true)
            && App::environment(config('LaravelHttps.ForceHttpsEnvironmentToCheck', 'production'))
        ) {
            return redirect()->secure($request->getRequestUri());
        }

        return $next($request);
    }
}
