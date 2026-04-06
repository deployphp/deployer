<?php

declare(strict_types=1);

namespace Deployer\Ssh;

use Deployer\Deployer;
use Deployer\Host\Localhost;
use Deployer\Logger\Logger;
use Deployer\Logger\Handler\HandlerInterface;
use Deployer\Task\Context;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;

#[Group('integration')]
class SshClientTest extends TestCase
{
    private Deployer $deployer;
    private Localhost $host;
    private BufferedOutput $verboseOutput;
    private SshClient $client;

    protected function setUp(): void
    {
        $console = new Application();
        $input = $this->createStub(Input::class);
        $output = $this->createStub(Output::class);

        $this->deployer = new Deployer($console);
        $this->deployer['input'] = $input;
        $this->deployer['output'] = $output;

        $this->host = new Localhost('test-host');

        // Push context so config callbacks (like 'shell') can resolve
        Context::push(new Context($this->host));

        $stubOutput = $this->createStub(OutputInterface::class);
        $stubOutput->method('isDebug')->willReturn(false);

        $this->verboseOutput = new BufferedOutput();
        $this->verboseOutput->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);

        $fileLog = $this->createStub(HandlerInterface::class);
        $logger = new Logger($this->verboseOutput, $fileLog);
        $this->client = new SshClient($stubOutput, $logger);
    }

    protected function tearDown(): void
    {
        Context::pop();
        unset($this->deployer);
    }

    public function testRunPrependsCwd(): void
    {
        $params = new RunParams(cwd: '/var/www', nothrow: true);

        try {
            $this->client->run($this->host, 'ls', $params);
        } catch (\Throwable) {
        }

        $logged = $this->verboseOutput->fetch();
        self::assertStringContainsString('cd /var/www && (ls)', $logged);
    }

    public function testRunPrependsEnvExport(): void
    {
        $params = new RunParams(env: ['APP_ENV' => 'prod'], nothrow: true);

        try {
            $this->client->run($this->host, 'deploy', $params);
        } catch (\Throwable) {
        }

        $logged = $this->verboseOutput->fetch();
        self::assertStringContainsString('export', $logged);
        self::assertStringContainsString('APP_ENV', $logged);
    }

    public function testRunLogsBareCommandWithoutCwdOrEnv(): void
    {
        $params = new RunParams(nothrow: true);

        try {
            $this->client->run($this->host, 'whoami', $params);
        } catch (\Throwable) {
        }

        $logged = $this->verboseOutput->fetch();
        self::assertStringContainsString('run', $logged);
        self::assertStringContainsString('whoami', $logged);
    }
}
