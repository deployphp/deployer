<?php

declare(strict_types=1);

namespace Deployer\Utility;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
class HttpieTest extends TestCase
{
    // ─── Static factories ────────────────────────────────────────

    public function testGet(): void
    {
        $http = Httpie::get('https://example.com');
        self::assertSame('GET', $http->getMethod());
        self::assertSame('https://example.com', $http->getUrl());
    }

    public function testPost(): void
    {
        $http = Httpie::post('https://example.com');
        self::assertSame('POST', $http->getMethod());
    }

    public function testPut(): void
    {
        $http = Httpie::put('https://example.com');
        self::assertSame('PUT', $http->getMethod());
    }

    public function testPatch(): void
    {
        $http = Httpie::patch('https://example.com');
        self::assertSame('PATCH', $http->getMethod());
    }

    public function testDelete(): void
    {
        $http = Httpie::delete('https://example.com');
        self::assertSame('DELETE', $http->getMethod());
    }

    // ─── Query ───────────────────────────────────────────────────

    public function testQuery(): void
    {
        $http = Httpie::get('https://example.com')
            ->query(['foo' => 'bar', 'baz' => '1']);
        self::assertSame('https://example.com?foo=bar&baz=1', $http->getUrl());
    }

    public function testQueryAppendsWithAmpersandWhenUrlHasQuery(): void
    {
        $http = Httpie::get('https://example.com?existing=1')
            ->query(['new' => '2']);
        self::assertSame('https://example.com?existing=1&new=2', $http->getUrl());
    }

    public function testQueryCalledTwice(): void
    {
        $http = Httpie::get('https://example.com')
            ->query(['a' => '1'])
            ->query(['b' => '2']);
        self::assertSame('https://example.com?a=1&b=2', $http->getUrl());
    }

    // ─── Headers ─────────────────────────────────────────────────

    public function testHeader(): void
    {
        $http = Httpie::get('https://example.com')
            ->header('X-Custom', 'value');
        self::assertSame('value', $http->getHeaders()['X-Custom']);
    }

    public function testBearerToken(): void
    {
        $http = Httpie::get('https://example.com')
            ->bearerToken('my-secret');
        self::assertSame('Bearer my-secret', $http->getHeaders()['Authorization']);
    }

    // ─── Auth ────────────────────────────────────────────────────

    public function testBasicAuth(): void
    {
        $http = Httpie::get('https://example.com')
            ->basicAuth('user', 'pass');
        self::assertSame('user:pass', $http->getCurlopts()[CURLOPT_USERPWD]);
    }

    // ─── Timeout ─────────────────────────────────────────────────

    public function testTimeout(): void
    {
        $http = Httpie::get('https://example.com')
            ->timeout(30);
        self::assertSame(30, $http->getCurlopts()[CURLOPT_TIMEOUT]);
        self::assertSame(30, $http->getCurlopts()[CURLOPT_CONNECTTIMEOUT]);
    }

    public function testNoTimeout(): void
    {
        $http = Httpie::get('https://example.com')
            ->noTimeout();
        self::assertSame(0, $http->getCurlopts()[CURLOPT_TIMEOUT]);
        self::assertSame(0, $http->getCurlopts()[CURLOPT_CONNECTTIMEOUT]);
    }

    // ─── Body ────────────────────────────────────────────────────

    public function testBody(): void
    {
        $http = Httpie::post('https://example.com')
            ->body('raw content');
        self::assertSame('raw content', $http->getBody());
        self::assertSame('11', $http->getHeaders()['Content-Length']);
    }

    public function testJsonBody(): void
    {
        $http = Httpie::post('https://example.com')
            ->jsonBody(['key' => 'value']);
        self::assertSame('application/json', $http->getHeaders()['Content-Type']);
        self::assertStringContainsString('"key": "value"', $http->getBody());
    }

    public function testFormBody(): void
    {
        $http = Httpie::post('https://example.com')
            ->formBody(['key' => 'value']);
        self::assertSame('application/x-www-form-urlencoded', $http->getHeaders()['Content-type']);
        self::assertSame('key=value', $http->getBody());
    }

    // ─── Nothrow ─────────────────────────────────────────────────

    public function testNothrow(): void
    {
        if (!defined('DEPLOYER_VERSION')) {
            define('DEPLOYER_VERSION', 'test');
        }
        // nothrow with a bad URL should not throw.
        $response = Httpie::get('http://0.0.0.0:1')
            ->timeout(1)
            ->nothrow()
            ->send();
        self::assertSame('', $response->body());
    }

    // ─── Fluent chaining ─────────────────────────────────────────

    public function testFluentChaining(): void
    {
        $http = Httpie::post('https://example.com')
            ->bearerToken('tok')
            ->noTimeout()
            ->header('Accept', 'application/json')
            ->jsonBody(['a' => 1]);

        self::assertSame('POST', $http->getMethod());
        self::assertSame('Bearer tok', $http->getHeaders()['Authorization']);
        self::assertSame('application/json', $http->getHeaders()['Accept']);
        self::assertSame(0, $http->getCurlopts()[CURLOPT_TIMEOUT]);
        self::assertStringContainsString('"a": 1', $http->getBody());
    }
}
