<?php

namespace jeremykenedy\LaravelHttps\Tests;

use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\TranslationServiceProvider;
use Illuminate\View\ViewServiceProvider;
use jeremykenedy\LaravelHttps\LaravelHttpsServiceProvider;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected $app;

    protected $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/laravel-https-'.bin2hex(random_bytes(8));

        foreach (['config', 'resources/views', 'resources/lang', 'storage/framework/views', 'bootstrap/cache'] as $path) {
            mkdir($this->directory.'/'.$path, 0777, true);
        }

        $this->app = new Application($this->directory);
        $this->app->instance('env', 'testing');
        $this->app->instance('config', new Repository([
            'app' => ['locale' => 'en', 'fallback_locale' => 'en', 'timezone' => 'UTC', 'key' => str_repeat('a', 32)],
            'view' => [
                'paths' => [$this->directory.'/resources/views'],
                'compiled' => $this->directory.'/storage/framework/views',
            ],
        ]));
        $this->app->instance('path.lang', $this->directory.'/resources/lang');

        if (method_exists($this->app, 'useLangPath')) {
            $this->app->useLangPath($this->directory.'/resources/lang');
        }

        $this->app->instance('request', Request::create('http://localhost'));
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->app);
        $this->app->register(FilesystemServiceProvider::class);
        $this->app->register(TranslationServiceProvider::class);
        $this->app->register(ViewServiceProvider::class);
    }

    protected function bootPackage()
    {
        $this->app->register(LaravelHttpsServiceProvider::class);
        $this->app->boot();
    }

    protected function request($uri = 'http://example.com/private', array $server = [], $method = 'GET')
    {
        $request = Request::create($uri, $method, [], [], [], $server);
        $this->app->instance('request', $request);
        $this->app['url']->setRequest($request);

        return $request;
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        $this->app->flush();
        (new Filesystem)->deleteDirectory($this->directory);
        Request::setTrustedProxies([], -1);

        parent::tearDown();
    }
}
