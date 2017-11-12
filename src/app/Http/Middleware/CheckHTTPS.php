<?php

namespace jeremykenedy\LaravelHttps\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Response;

class CheckHTTPS
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

            $errorMessage = trans('LaravelHttps::laravel-https.messages.httpsRequredError') . trans('LaravelHttps::laravel-https.messages.httpsRequred');
            $errorCode = config('LaravelHttps.httpsAccessDeniedErrorCode');

            if ($request->ajax() || $request->wantsJson()) {
                 return Response::json(array(
                    'code'      =>  $errorCode,
                    'message'   =>  $errorMessage
                ), $errorCode);
            }

            try {
                return response()->view('LaravelHttps::errors.'.$errorCode);
            } catch (Exception $e) {
                \App::abort($errorCode, $errorMessage);
            }

        }

        return $next($request);
    }
}
