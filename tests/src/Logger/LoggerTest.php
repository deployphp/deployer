<?php

declare(strict_types=1);

namespace Deployer\Logger;

use Deployer\Deployer;
use Deployer\Exception\RunException;
use Deployer\Host\Localhost;
use Deployer\Logger\Handler\HandlerInterface;
use Deployer\Task\Task;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Console\Output\OutputInterface;

#[Group('unit')]
class LoggerTest extends TestCase
{
    private Deployer $deployer;
    private Localhost $host;

    protected function setUp(): void
    {
        $console = new Application();
        $input = $this->createStub(Input::class);
        $stubOutput = $this->createStub(Output::class);

        $this->deployer = new Deployer($console);
        $this->deployer['input'] = $input;
        $this->deployer['output'] = $stubOutput;

        $this->host = new Localhost('test-host');

        // Clear CI env vars
        putenv('GITHUB_WORKFLOW');
        putenv('GITLAB_CI');
    }

    protected function tearDown(): void
    {
        putenv('GITHUB_WORKFLOW');
        putenv('GITLAB_CI');
        unset($this->deployer);
    }

    public function testCommandWritesToFileLogAlways(): void
    {
        $output = $this->createStub(OutputInterface::class);
        $output->method('isVerbose')->willReturn(false);

        $fileLog = $this->createMock(HandlerInterface::class);
        $fileLog->expects($this->once())
            ->method('writeln')
            ->with($this->stringContains('run: echo hello'));

        $logger = new Logger($output, $fileLog);
        $logger->command($this->host, 'run', 'echo hello');
    }

    public function testCommandWritesToOutputWhenVerbose(): void
    {
        $output = $this->createMock(OutputInterface::class);
        $output->method('isVerbose')->willReturn(true);
        $output->expects($this->once())
            ->method('writeln')
            ->with($this->stringContains('run'));

        $fileLog = $this->createStub(HandlerInterface::class);

        $logger = new Logger($output, $fileLog);
        $logger->command($this->host, 'run', 'echo hello');
    }

    public function testCommandDoesNotWriteToOutputWhenNotVerbose(): void
    {
        $output = $this->createMock(OutputInterface::class);
        $output->method('isVerbose')->willReturn(false);
        $output->expects($this->never())
            ->method('writeln');

        $fileLog = $this->createStub(HandlerInterface::class);

        $logger = new Logger($output, $fileLog);
        $logger->command($this->host, 'run', 'echo hello');
    }

    public function testPrintWritesToOutputWhenForced(): void
    {
        $output = $this->createMock(OutputInterface::class);
        $output->method('isVerbose')->willReturn(false);
        $output->expects($this->once())
            ->method('writeln')
            ->with($this->stringContains('output line'));

        $fileLog = $this->createStub(HandlerInterface::class);

        $logger = new Logger($output, $fileLog);
        $logger->print($this->host, "output line\n", true);
    }

    public function testStartTaskDefault(): void
    {
        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->once())
            ->method('writeln')
            ->with($this->stringContains('deploy'));

        $fileLog = $this->createStub(HandlerInterface::class);

        $logger = new Logger($output, $fileLog);
        $logger->startTask(new Task('deploy', function () {}));
    }

    public function testStartTaskGithubCI(): void
    {
        putenv('GITHUB_WORKFLOW=test');

        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->once())
            ->method('writeln')
            ->with('::group::task deploy');

        $fileLog = $this->createStub(HandlerInterface::class);

        $logger = new Logger($output, $fileLog);
        $logger->startTask(new Task('deploy', function () {}));
    }

    public function testStartTaskGitlabCI(): void
    {
        putenv('GITLAB_CI=true');

        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->once())
            ->method('writeln')
            ->with($this->stringContains('section_start:'));

        $fileLog = $this->createStub(HandlerInterface::class);

        $logger = new Logger($output, $fileLog);
        $logger->startTask(new Task('deploy', function () {}));
    }

    public function testEndOnHostWritesToFileLog(): void
    {
        $output = $this->createStub(OutputInterface::class);
        $output->method('isVeryVerbose')->willReturn(false);

        $fileLog = $this->createMock(HandlerInterface::class);
        $fileLog->expects($this->once())
            ->method('writeln')
            ->with($this->stringContains('done on'));

        $logger = new Logger($output, $fileLog);
        $logger->endOnHost($this->host);
    }

    public function testEndOnHostWritesToOutputWhenVeryVerbose(): void
    {
        $output = $this->createMock(OutputInterface::class);
        $output->method('isVeryVerbose')->willReturn(true);
        $output->expects($this->once())
            ->method('writeln')
            ->with($this->stringContains('done'));

        $fileLog = $this->createStub(HandlerInterface::class);

        $logger = new Logger($output, $fileLog);
        $logger->endOnHost($this->host);
    }

    public function testRenderExceptionRunException(): void
    {
        $exception = new RunException($this->host, 'bad-cmd', 1, 'out', 'err');
        $exception->setTaskFilename('deploy.php');
        $exception->setTaskLineNumber(42);

        $output = $this->createMock(OutputInterface::class);
        $output->method('getVerbosity')
            ->willReturn(OutputInterface::VERBOSITY_NORMAL);
        $output->expects($this->once())
            ->method('write')
            ->with($this->logicalAnd(
                $this->stringContains('error'),
                $this->stringContains('exit code'),
                $this->stringContains('bad-cmd'),
            ));

        $fileLog = $this->createStub(HandlerInterface::class);

        $logger = new Logger($output, $fileLog);
        $logger->renderException($exception, $this->host);
    }

    public function testRenderExceptionGeneric(): void
    {
        $exception = new \RuntimeException('something broke');

        $output = $this->createMock(OutputInterface::class);
        $output->method('isDebug')->willReturn(false);
        $output->expects($this->once())
            ->method('write')
            ->with($this->logicalAnd(
                $this->stringContains('RuntimeException'),
                $this->stringContains('something broke'),
            ));

        $fileLog = $this->createStub(HandlerInterface::class);

        $logger = new Logger($output, $fileLog);
        $logger->renderException($exception, $this->host);
    }
}
