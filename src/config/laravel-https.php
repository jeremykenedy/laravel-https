<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Laravel Https Settings
    |--------------------------------------------------------------------------
    */
    'ForceHttpsCheckEnvironment' => env('LARAVEL_FORCEHTTPSCHECKENVIRONMENT', true),
    'ForceHttpsEnvironmentToCheck' => env('LARAVEL_FORCEHTTPSENVIRONMENTTOCHECK', 'production'),
    'httpsAccessDeniedErrorCode' => env('LARAVEL_HTTP_ERROR_CODE', 403),

    // Keep the original HTML status unless the application opts into a denial status.
    'httpsAccessDeniedHtmlStatus' => env('LARAVEL_HTTPS_HTML_STATUS', 200),

];
