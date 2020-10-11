<?php declare(strict_types=1);
namespace e2e;

use Deployer\AbstractTest;

abstract class AbstractE2ETest extends AbstractTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $isE2EEnvironment = filter_var(getenv('E2E_ENV'), FILTER_VALIDATE_BOOLEAN);

        if ($isE2EEnvironment !== true) {
            $this->markTestSkipped('Cannot execute in non-E2E environment');
        }
    }
}
