<?php

namespace jeremykenedy\LaravelHttps;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use jeremykenedy\LaravelHttps\App\Http\Middleware\CheckHTTPS;
use jeremykenedy\LaravelHttps\App\Http\Middleware\ForceHTTPS;

class LaravelHttpsServiceProvider extends ServiceProvider
{
    protected $defer = false;

    public function boot(Router $router)
    {
        if (method_exists($router, 'middlewareGroup')) {
            $router->middlewareGroup('checkHTTPS', [CheckHTTPS::class]);
            $router->middlewareGroup('forceHTTPS', [ForceHTTPS::class]);
        } else {
            $router->middleware('checkHTTPS', CheckHTTPS::class);
            $router->middleware('forceHTTPS', ForceHTTPS::class);
        }

        $this->loadTranslationsFrom(__DIR__.'/resources/lang/', 'LaravelHttps');
    }

    public function register()
    {
        $path = $this->app->basePath().'/resources/lang/vendor/laravel-https';
        $languagePath = $this->app['path.lang'];

        $this->app->extend('translation.loader', function ($loader) use ($path, $languagePath) {
            return new PublishedTranslationLoader($loader, $path, $languagePath);
        });

        $this->loadViewsFrom([
            $this->app->basePath().'/resources/views/vendor/laravel-https',
            __DIR__.'/resources/views/',
        ], 'LaravelHttps');

        $config = $this->app['config'];
        $config->set('LaravelHttps', array_merge(
            $config->get('laravel-https', []),
            $config->get('LaravelHttps', [])
        ));
        $this->mergeConfigFrom(__DIR__.'/config/laravel-https.php', 'LaravelHttps');
        $this->publishFiles();
    }

    private function publishFiles()
    {
        $this->publishes([
            __DIR__.'/config/laravel-https.php' => $this->app->basePath().'/config/laravel-https.php',
            __DIR__.'/resources/views' => $this->app->basePath().'/resources/views/vendor/laravel-https',
            __DIR__.'/resources/lang' => $this->app->basePath().'/resources/lang/vendor/laravel-https',
        ], 'LaravelHttps');
    }
}
