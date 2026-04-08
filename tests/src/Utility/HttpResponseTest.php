<?php

declare(strict_types=1);

namespace Deployer\Utility;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
class HttpResponseTest extends TestCase
{
    public function testBody(): void
    {
        $response = new HttpResponse('hello', ['http_code' => 200]);
        self::assertSame('hello', $response->body());
    }

    public function testStatus(): void
    {
        $response = new HttpResponse('', ['http_code' => 404]);
        self::assertSame(404, $response->status());
    }

    public function testStatusDefaultsToZero(): void
    {
        $response = new HttpResponse('', []);
        self::assertSame(0, $response->status());
    }

    public function testJson(): void
    {
        $response = new HttpResponse('{"key":"value","n":42}', ['http_code' => 200]);
        self::assertSame(['key' => 'value', 'n' => 42], $response->json());
    }

    public function testJsonThrowsOnInvalidJson(): void
    {
        $response = new HttpResponse('not json', ['http_code' => 200]);
        $this->expectException(\JsonException::class);
        $response->json();
    }

    public function testInfo(): void
    {
        $info = ['http_code' => 200, 'content_type' => 'application/json'];
        $response = new HttpResponse('', $info);
        self::assertSame($info, $response->info());
    }

    public function testToString(): void
    {
        $response = new HttpResponse('body content', ['http_code' => 200]);
        self::assertSame('body content', (string) $response);
    }
}
