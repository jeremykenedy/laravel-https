<?php

namespace jeremykenedy\LaravelHttps\Tests;

use Illuminate\Http\Response;
use jeremykenedy\LaravelHttps\App\Http\Middleware\CheckHTTPS;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CheckHTTPSTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->bootPackage();
    }

    public function test_secure_requests_pass_through_without_changing_the_response()
    {
        $request = $this->request('https://example.com/private');
        $expected = new Response('private content', 201);
        $actual = (new CheckHTTPS)->handle($request, function ($received) use ($request, $expected) {
            $this->assertSame($request, $received);

            return $expected;
        });

        $this->assertSame($expected, $actual);
    }

    public function test_json_denial_preserves_the_response_contract()
    {
        $response = $this->deny(['HTTP_ACCEPT' => 'application/json']);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertSame([
            'code' => 403,
            'message' => '403 | ForbiddenSSL(HTTPS) is required to view',
        ], json_decode($response->getContent(), true));
    }

    public function test_ajax_requests_receive_json_without_an_accept_header()
    {
        $response = $this->deny(['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(403, json_decode($response->getContent(), true)['code']);
    }

    public function test_vendor_json_accept_headers_receive_json()
    {
        $response = $this->deny(['HTTP_ACCEPT' => 'application/vnd.api+json']);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
    }

    public function test_custom_json_status_and_string_environment_values_are_preserved()
    {
        $this->app['config']->set('LaravelHttps.httpsAccessDeniedErrorCode', '426');
        $response = $this->deny(['HTTP_ACCEPT' => 'application/json']);

        $this->assertSame(426, $response->getStatusCode());
        $this->assertSame('426', json_decode($response->getContent(), true)['code']);
    }

    public function test_html_denial_preserves_the_legacy_view_and_status()
    {
        $response = $this->deny();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('403 | Forbidden', $response->getContent());
        $this->assertStringContainsString('SSL(HTTPS) is required to view', $response->getContent());
    }

    public function test_html_status_can_be_changed_explicitly()
    {
        $this->app['config']->set('LaravelHttps.httpsAccessDeniedHtmlStatus', 403);

        $this->assertSame(403, $this->deny()->getStatusCode());
    }

    public function test_missing_error_views_abort_with_the_configured_status()
    {
        $this->app['config']->set('LaravelHttps.httpsAccessDeniedErrorCode', 426);

        try {
            $this->deny();
            $this->fail('A missing view must produce an HTTP denial.');
        } catch (HttpException $exception) {
            $this->assertSame(426, $exception->getStatusCode());
            $this->assertSame('403 | ForbiddenSSL(HTTPS) is required to view', $exception->getMessage());
        }
    }

    public function test_check_middleware_does_not_depend_on_the_environment()
    {
        $this->app->instance('env', 'local');
        $this->app['config']->set('LaravelHttps.ForceHttpsCheckEnvironment', false);

        $this->assertSame(403, $this->deny(['HTTP_ACCEPT' => 'application/json'])->getStatusCode());
    }

    public function test_untrusted_forwarded_proto_cannot_bypass_the_check()
    {
        $response = $this->deny(['HTTP_X_FORWARDED_PROTO' => 'https', 'REMOTE_ADDR' => '192.0.2.1']);

        $this->assertStringContainsString('403 | Forbidden', $response->getContent());
    }

    public function test_registered_middleware_protects_a_route()
    {
        $this->app['router']->get('/private', function () {
            return 'private content';
        })->middleware('checkHTTPS');

        $denied = $this->app['router']->dispatch($this->request('http://example.com/private', ['HTTP_ACCEPT' => 'application/json']));
        $allowed = $this->app['router']->dispatch($this->request('https://example.com/private'));

        $this->assertSame(403, $denied->getStatusCode());
        $this->assertSame('private content', $allowed->getContent());
    }

    private function deny(array $server = [])
    {
        return (new CheckHTTPS)->handle($this->request('http://example.com/private', $server), function () {
            $this->fail('Insecure requests must not reach the next middleware.');
        });
    }
}
