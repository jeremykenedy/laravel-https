<?php

namespace jeremykenedy\LaravelHttps\App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;

class CheckHTTPS
{
    public function handle($request, Closure $next)
    {
        if (! $request->secure()) {
            $errorMessage = trans('LaravelHttps::laravel-https.messages.httpsRequredError').trans('LaravelHttps::laravel-https.messages.httpsRequred');
            $errorCode = config('LaravelHttps.httpsAccessDeniedErrorCode');

            if ($request->ajax() || $request->wantsJson()) {
                return Response::json([
                    'code' => $errorCode,
                    'message' => $errorMessage,
                ], $errorCode);
            }

            $view = 'LaravelHttps::errors.'.$errorCode;

            if (View::exists($view)) {
                return response()->view($view, [], config('LaravelHttps.httpsAccessDeniedHtmlStatus', 200));
            }

            App::abort($errorCode, $errorMessage);
        }

        return $next($request);
    }
}
