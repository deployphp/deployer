<?php

declare(strict_types=1);

namespace Deployer\Executor;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
class ResponseTest extends TestCase
{
    public function testGetters(): void
    {
        $response = new Response(200, ['key' => 'value']);
        self::assertSame(200, $response->getStatus());
        self::assertSame(['key' => 'value'], $response->getBody());
    }

    public function testNullBody(): void
    {
        $response = new Response(404, null);
        self::assertSame(404, $response->getStatus());
        self::assertNull($response->getBody());
    }

    public function testStringBody(): void
    {
        $response = new Response(200, 'hello');
        self::assertSame('hello', $response->getBody());
    }
}
