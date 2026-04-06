<?php

declare(strict_types=1);

namespace Deployer\Executor;

use Deployer\Deployer;
use Deployer\Exception\GracefulShutdownException;
use Deployer\Exception\RunException;
use Deployer\Host\Host;
use Deployer\Host\Localhost;
use Deployer\Logger\Logger;
use Deployer\Task\Task;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\Input;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Console\Output\Output;

#[Group('unit')]
class WorkerTest extends TestCase
{
    private Deployer $deployer;
    private Logger $logger;
    private Worker $worker;

    protected function setUp(): void
    {
        $console = new Application();
        $input = $this->createStub(Input::class);
        $output = $this->createStub(Output::class);

        $this->deployer = new Deployer($console);
        $this->deployer['input'] = $input;
        $this->deployer['output'] = $output;

        $this->logger = $this->createMock(Logger::class);
        $this->deployer['logger'] = $this->logger;

        $this->worker = new Worker($this->deployer);
    }

    protected function tearDown(): void
    {
        unset($this->deployer);
    }

    public function testExecuteSuccessReturnsZero(): void
    {
        $host = new Localhost('test-host');
        $task = new Task('deploy', function () {});

        $this->logger->expects($this->once())
            ->method('endOnHost')
            ->with($host);

        $exitCode = $this->worker->execute($task, $host);
        self::assertSame(0, $exitCode);
    }

    public function testExecuteSkipsLoggerForConnectTask(): void
    {
        $host = new Localhost('test-host');
        $task = new Task('connect', function () {});

        $this->logger->expects($this->never())
            ->method('endOnHost');

        $exitCode = $this->worker->execute($task, $host);
        self::assertSame(0, $exitCode);
    }

    public function testExecuteGracefulShutdownExceptionReturnsExitCode42(): void
    {
        $host = new Localhost('test-host');
        $task = new Task('deploy', function () {
            throw new GracefulShutdownException('graceful shutdown');
        });

        $this->logger->expects($this->once())
            ->method('renderException');

        $exitCode = $this->worker->execute($task, $host);
        self::assertSame(GracefulShutdownException::EXIT_CODE, $exitCode);
    }

    public function testExecuteRunExceptionReturnsItsExitCode(): void
    {
        $host = new Localhost('test-host');
        $task = new Task('deploy', function () use ($host) {
            throw new RunException($host, 'failing-command', 127, '', 'command not found');
        });

        $this->logger->expects($this->once())
            ->method('renderException');

        $exitCode = $this->worker->execute($task, $host);
        self::assertSame(127, $exitCode);
    }

    public function testExecuteGenericThrowableReturns255(): void
    {
        $host = new Localhost('test-host');
        $task = new Task('deploy', function () {
            throw new \RuntimeException('unexpected error');
        });

        $this->logger->expects($this->once())
            ->method('renderException');

        $exitCode = $this->worker->execute($task, $host);
        self::assertSame(255, $exitCode);
    }
}
