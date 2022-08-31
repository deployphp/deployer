<?php declare(strict_types=1);
namespace e2e;

use Deployer\Exception\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;

abstract class AbstractE2ETest extends TestCase
{
    /** @var ConsoleApplicationTester */
    protected $tester;

    public function setUp(): void
    {
        $this->tester = new ConsoleApplicationTester(__DIR__ . '/../../bin/dep', __DIR__);
    }
}
