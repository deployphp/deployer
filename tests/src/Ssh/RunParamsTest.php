<?php

declare(strict_types=1);

namespace Deployer\Ssh;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
class RunParamsTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $params = new RunParams();
        self::assertNull($params->shell);
        self::assertNull($params->cwd);
        self::assertNull($params->env);
        self::assertNull($params->dotenv);
        self::assertFalse($params->nothrow);
        self::assertNull($params->timeout);
        self::assertNull($params->idleTimeout);
        self::assertFalse($params->forceOutput);
        self::assertNull($params->secrets);
    }

    public function testWithMergesSecrets(): void
    {
        $params = new RunParams(secrets: ['key1' => 'val1']);
        $newParams = $params->with(secrets: ['key2' => 'val2']);

        self::assertSame(['key1' => 'val1', 'key2' => 'val2'], $newParams->secrets);
        // Original is unchanged
        self::assertSame(['key1' => 'val1'], $params->secrets);
    }

    public function testWithOverridesTimeout(): void
    {
        $params = new RunParams(timeout: 30);
        $newParams = $params->with(timeout: 60);

        self::assertSame(60, $newParams->timeout);
        // Original is unchanged
        self::assertSame(30, $params->timeout);
    }

    public function testWithKeepsTimeoutWhenNull(): void
    {
        $params = new RunParams(timeout: 30);
        $newParams = $params->with(secrets: ['key' => 'val']);

        self::assertSame(30, $newParams->timeout);
    }

    public function testWithMergesSecretsFromNull(): void
    {
        $params = new RunParams();
        $newParams = $params->with(secrets: ['key' => 'val']);

        self::assertSame(['key' => 'val'], $newParams->secrets);
    }

    public function testConstructorWithAllParams(): void
    {
        $params = new RunParams(
            shell: 'bash -s',
            cwd: '/var/www',
            env: ['APP_ENV' => 'prod'],
            dotenv: '/var/www/.env',
            nothrow: true,
            timeout: 120,
            idleTimeout: 60,
            forceOutput: true,
            secrets: ['DB_PASS' => 'secret'],
        );

        self::assertSame('bash -s', $params->shell);
        self::assertSame('/var/www', $params->cwd);
        self::assertSame(['APP_ENV' => 'prod'], $params->env);
        self::assertSame('/var/www/.env', $params->dotenv);
        self::assertTrue($params->nothrow);
        self::assertSame(120, $params->timeout);
        self::assertSame(60, $params->idleTimeout);
        self::assertTrue($params->forceOutput);
        self::assertSame(['DB_PASS' => 'secret'], $params->secrets);
    }
}
