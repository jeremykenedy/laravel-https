<?php

namespace jeremykenedy\LaravelHttps;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use jeremykenedy\LaravelHttps\App\Http\Middleware\CheckHTTPS;
use jeremykenedy\LaravelHttps\App\Http\Middleware\ForceHTTPS;

class LaravelHttpsServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(Router $router)
    {
        $router->middlewareGroup('checkHTTPS',[CheckHTTPS::class]);
        $router->middlewareGroup('forceHTTPS',[ForceHTTPS::class]);
        $this->loadTranslationsFrom(__DIR__.'/resources/lang/', 'LaravelHttps');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
       $this->loadViewsFrom(__DIR__.'/resources/views/', 'LaravelHttps');
       $this->mergeConfigFrom(__DIR__.'/config/laravel-https.php', 'LaravelHttps');
       $this->publishFiles();
    }

    /**
     * Publish files for Laravel Logger.
     *
     * @return void
     */
    private function publishFiles()
    {
        $publishTag = 'LaravelHttps';

        $this->publishes([
            __DIR__.'/config/laravel-https.php' => base_path('config/laravel-https.php'),
        ], $publishTag);

        $this->publishes([
            __DIR__.'/resources/views' => base_path('resources/views/vendor/laravel-https'),
        ], $publishTag);

        $this->publishes([
            __DIR__.'/resources/lang' => base_path('resources/lang/vendor/laravel-https'),
        ], $publishTag);

    }

}
