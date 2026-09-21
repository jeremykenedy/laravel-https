<?php

namespace jeremykenedy\LaravelHttps\Tests;

use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Translation\FileLoader;
use jeremykenedy\LaravelHttps\App\Http\Middleware\CheckHTTPS;
use jeremykenedy\LaravelHttps\LaravelHttpsServiceProvider;

class ServiceProviderTest extends TestCase
{
    public function test_default_configuration_is_available_without_publishing()
    {
        $this->bootPackage();

        $this->assertTrue($this->app['config']->get('LaravelHttps.ForceHttpsCheckEnvironment'));
        $this->assertSame('production', $this->app['config']->get('LaravelHttps.ForceHttpsEnvironmentToCheck'));
        $this->assertSame(403, $this->app['config']->get('LaravelHttps.httpsAccessDeniedErrorCode'));
        $this->assertSame(200, $this->app['config']->get('LaravelHttps.httpsAccessDeniedHtmlStatus'));
    }

    public function test_published_configuration_is_used_without_losing_defaults()
    {
        $this->app['config']->set('laravel-https', ['httpsAccessDeniedErrorCode' => 426]);
        $this->bootPackage();

        $this->assertSame(426, $this->app['config']->get('LaravelHttps.httpsAccessDeniedErrorCode'));
        $this->assertSame('production', $this->app['config']->get('LaravelHttps.ForceHttpsEnvironmentToCheck'));
    }

    public function test_legacy_configuration_takes_precedence()
    {
        $this->app['config']->set('laravel-https', ['httpsAccessDeniedErrorCode' => 426]);
        $this->app['config']->set('LaravelHttps', ['httpsAccessDeniedErrorCode' => 401]);
        $this->bootPackage();

        $this->assertSame(401, $this->app['config']->get('LaravelHttps.httpsAccessDeniedErrorCode'));
    }

    public function test_existing_publish_tag_and_paths_are_preserved()
    {
        $this->bootPackage();
        $paths = LaravelHttpsServiceProvider::pathsToPublish(LaravelHttpsServiceProvider::class, 'LaravelHttps');

        $this->assertSame([
            dirname(__DIR__).'/src/config/laravel-https.php' => $this->directory.'/config/laravel-https.php',
            dirname(__DIR__).'/src/resources/views' => $this->directory.'/resources/views/vendor/laravel-https',
            dirname(__DIR__).'/src/resources/lang' => $this->directory.'/resources/lang/vendor/laravel-https',
        ], $paths);
        $this->assertFalse(file_exists($this->directory.'/config/laravel-https.php'));
    }

    public function test_published_view_is_rendered()
    {
        $this->writeView('laravel-https', 'Published denial');
        $this->bootPackage();

        $this->assertSame('Published denial', $this->app['view']->make('LaravelHttps::errors.403')->render());
    }

    public function test_existing_namespace_view_overrides_remain_supported()
    {
        $this->writeView('LaravelHttps', 'Existing denial');
        $this->writeView('laravel-https', 'Published denial');
        $this->bootPackage();

        $this->assertSame('Existing denial', $this->app['view']->make('LaravelHttps::errors.403')->render());
    }

    public function test_published_translations_override_only_the_provided_messages()
    {
        $path = $this->directory.'/resources/lang/vendor/laravel-https/en';
        mkdir($path, 0777, true);
        copy(__DIR__.'/Fixtures/translation.php', $path.'/laravel-https.php');
        $this->bootPackage();
        $response = (new CheckHTTPS)->handle($this->request('http://example.com', ['HTTP_ACCEPT' => 'application/json']), function () {
            $this->fail('The request must be denied.');
        });

        $this->assertSame('403 | ForbiddenUse HTTPS.', json_decode($response->getContent(), true)['message']);
    }

    public function test_published_translations_follow_locale_changes()
    {
        $path = $this->directory.'/resources/lang/vendor/laravel-https/fr';
        mkdir($path, 0777, true);
        copy(__DIR__.'/Fixtures/translation.php', $path.'/laravel-https.php');
        $this->bootPackage();
        $this->app['translator']->setLocale('fr');

        $this->assertSame('Use HTTPS.', $this->app['translator']->get('LaravelHttps::laravel-https.messages.httpsRequred'));
        $this->assertSame('403 | Forbidden', $this->app['translator']->get('LaravelHttps::laravel-https.messages.httpsRequredError'));
    }

    public function test_existing_namespace_translations_take_precedence()
    {
        foreach (['LaravelHttps', 'laravel-https'] as $namespace) {
            mkdir($this->directory.'/resources/lang/vendor/'.$namespace.'/en', 0777, true);
        }

        copy(__DIR__.'/Fixtures/translation.php', $this->directory.'/resources/lang/vendor/LaravelHttps/en/laravel-https.php');
        copy(dirname(__DIR__).'/src/resources/lang/en/laravel-https.php', $this->directory.'/resources/lang/vendor/laravel-https/en/laravel-https.php');
        $this->bootPackage();

        $this->assertSame('Use HTTPS.', $this->app['translator']->get('LaravelHttps::laravel-https.messages.httpsRequred'));
    }

    public function test_environment_variables_remain_supported()
    {
        foreach (['LARAVEL_HTTP_ERROR_CODE' => '426', 'LARAVEL_FORCEHTTPSCHECKENVIRONMENT' => 'false', 'LARAVEL_FORCEHTTPSENVIRONMENTTOCHECK' => 'staging', 'LARAVEL_HTTPS_HTML_STATUS' => '403'] as $key => $value) {
            putenv($key.'='.$value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        try {
            $this->bootPackage();
            $this->assertSame('426', $this->app['config']->get('LaravelHttps.httpsAccessDeniedErrorCode'));
            $this->assertFalse($this->app['config']->get('LaravelHttps.ForceHttpsCheckEnvironment'));
            $this->assertSame('staging', $this->app['config']->get('LaravelHttps.ForceHttpsEnvironmentToCheck'));
            $this->assertSame('403', $this->app['config']->get('LaravelHttps.httpsAccessDeniedHtmlStatus'));
        } finally {
            foreach (['LARAVEL_HTTP_ERROR_CODE', 'LARAVEL_FORCEHTTPSCHECKENVIRONMENT', 'LARAVEL_FORCEHTTPSENVIRONMENTTOCHECK', 'LARAVEL_HTTPS_HTML_STATUS'] as $key) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
            }
        }
    }

    public function test_other_translation_namespaces_are_unchanged()
    {
        $this->bootPackage();
        $loader = $this->app['translation.loader'];
        $loader->addNamespace('another-package', dirname(__DIR__).'/src/resources/lang');

        $this->assertSame('403 | Forbidden', $this->app['translator']->get('another-package::laravel-https.messages.httpsRequredError'));
        $this->assertSame([], $loader->load('en', 'missing', 'another-package'));

        if (method_exists(FileLoader::class, 'namespaces')) {
            $this->assertArrayHasKey('another-package', $loader->namespaces());
        }
    }

    public function test_application_json_translations_remain_available()
    {
        if (! method_exists(FileLoader::class, 'addJsonPath')) {
            $this->markTestSkipped('This Laravel version predates JSON translations.');
        }

        file_put_contents($this->directory.'/resources/lang/en.json', '{"Hello":"Welcome"}');
        $this->bootPackage();
        $loader = $this->app['translation.loader'];
        $loader->addJsonPath($this->directory.'/resources/lang');

        $this->assertSame('Welcome', $this->app['translator']->get('Hello'));

        if (method_exists(FileLoader::class, 'jsonPaths')) {
            $this->assertContains($this->directory.'/resources/lang', $loader->jsonPaths());
        }
    }

    public function test_additional_application_translation_paths_are_preserved()
    {
        if (! method_exists(FileLoader::class, 'addPath')) {
            $this->markTestSkipped('This Laravel version uses a single translation path.');
        }

        $this->bootPackage();
        $loader = $this->app['translation.loader'];
        $loader->addPath(dirname(__DIR__).'/src/resources/lang');

        $this->assertContains(dirname(__DIR__).'/src/resources/lang', $loader->paths());
        $this->assertSame('403 | Forbidden', $this->app['translator']->get('laravel-https.messages.httpsRequredError'));
    }

    public function test_cached_configuration_is_preserved()
    {
        $configuration = $this->app['config']->all();
        $configuration['LaravelHttps'] = [
            'ForceHttpsCheckEnvironment' => false,
            'ForceHttpsEnvironmentToCheck' => 'staging',
            'httpsAccessDeniedErrorCode' => 401,
            'httpsAccessDeniedHtmlStatus' => 403,
        ];
        file_put_contents($this->app->getCachedConfigPath(), '<?php return '.var_export($configuration, true).';');
        (new LoadConfiguration)->bootstrap($this->app);
        $this->bootPackage();

        $this->assertSame($configuration['LaravelHttps'], $this->app['config']->get('LaravelHttps'));
    }

    private function writeView($namespace, $content)
    {
        $path = $this->directory.'/resources/views/vendor/'.$namespace.'/errors';
        mkdir($path, 0777, true);
        file_put_contents($path.'/403.blade.php', $content);
    }
}
