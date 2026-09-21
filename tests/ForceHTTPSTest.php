<?php

namespace jeremykenedy\LaravelHttps\Tests;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use jeremykenedy\LaravelHttps\App\Http\Middleware\CheckHTTPS;
use jeremykenedy\LaravelHttps\App\Http\Middleware\ForceHTTPS;

class ForceHTTPSTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->bootPackage();
        $this->app->instance('env', 'production');
    }

    public function test_production_redirect_preserves_the_path_and_query_string()
    {
        $response = $this->redirect('http://example.com/a%20b?next=%2Fdashboard&tag=a&tag=b');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://example.com/a%20b?next=%2Fdashboard&tag=a&tag=b', $response->headers->get('Location'));
    }

    public function test_secure_requests_are_not_redirected()
    {
        $this->assertPassesThrough('https://example.com/private');
    }

    public function test_local_requests_are_not_redirected_by_default()
    {
        $this->app->instance('env', 'local');
        $this->assertPassesThrough();
    }

    public function test_disabling_the_environment_check_preserves_the_disabled_redirect_behavior()
    {
        $this->app['config']->set('LaravelHttps.ForceHttpsCheckEnvironment', false);
        $this->assertPassesThrough();
    }

    public function test_the_configured_environment_can_be_changed()
    {
        $this->app['config']->set('LaravelHttps.ForceHttpsEnvironmentToCheck', 'staging');
        $this->assertPassesThrough();
        $this->app->instance('env', 'staging');

        $this->assertSame(302, $this->redirect()->getStatusCode());
    }

    public function test_multiple_configured_environments_are_supported()
    {
        $this->app['config']->set('LaravelHttps.ForceHttpsEnvironmentToCheck', ['production', 'staging']);
        $this->app->instance('env', 'staging');

        $this->assertSame(302, $this->redirect()->getStatusCode());
    }

    public function test_post_requests_keep_the_existing302_redirect()
    {
        $this->assertSame(302, $this->redirect('http://example.com/private', 'POST')->getStatusCode());
    }

    public function test_untrusted_forwarded_proto_does_not_suppress_redirects()
    {
        $request = $this->request('http://example.com/private', [
            'REMOTE_ADDR' => '192.0.2.1', 'HTTP_X_FORWARDED_PROTO' => 'https',
        ]);
        $response = (new ForceHTTPS)->handle($request, function () {
            $this->fail('Untrusted headers must not suppress the redirect.');
        });

        $this->assertSame('https://example.com/private', $response->headers->get('Location'));
    }

    public function test_trusted_proxy_https_passes_through_both_middleware()
    {
        Request::setTrustedProxies(['192.0.2.1'], -1);
        $request = $this->request('http://example.com/private', [
            'REMOTE_ADDR' => '192.0.2.1', 'HTTP_X_FORWARDED_PROTO' => 'https',
        ]);
        $expected = new Response('secure');

        foreach ([new CheckHTTPS, new ForceHTTPS] as $middleware) {
            $this->assertSame($expected, $middleware->handle($request, function () use ($expected) {
                return $expected;
            }));
        }
    }

    public function test_registered_middleware_redirects_a_route()
    {
        $this->app['router']->get('/private', function () {
            return 'private content';
        })->middleware('forceHTTPS');

        $response = $this->app['router']->dispatch($this->request());

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://example.com/private', $response->headers->get('Location'));
    }

    private function redirect($uri = 'http://example.com/private', $method = 'GET')
    {
        return (new ForceHTTPS)->handle($this->request($uri, [], $method), function () {
            $this->fail('An insecure production request must be redirected.');
        });
    }

    private function assertPassesThrough($uri = 'http://example.com/private')
    {
        $request = $this->request($uri);
        $expected = new Response('unchanged');
        $actual = (new ForceHTTPS)->handle($request, function ($received) use ($request, $expected) {
            $this->assertSame($request, $received);

            return $expected;
        });

        $this->assertSame($expected, $actual);
    }
}
