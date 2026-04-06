<?php

declare(strict_types=1);

namespace Deployer\ProcessRunner;

use Deployer\Deployer;
use Deployer\Exception\RunException;
use Deployer\Host\Localhost;
use Deployer\Logger\Logger;
use Deployer\Logger\Handler\HandlerInterface;
use Deployer\Ssh\RunParams;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;

#[Group('integration')]
class ProcessRunnerTest extends TestCase
{
    private Deployer $deployer;
    private ProcessRunner $processRunner;
    private Localhost $host;

    protected function setUp(): void
    {
        $console = new Application();
        $input = $this->createStub(Input::class);
        $output = $this->createStub(Output::class);

        $this->deployer = new Deployer($console);
        $this->deployer['input'] = $input;
        $this->deployer['output'] = $output;

        $logger = new Logger(
            $this->createStub(\Symfony\Component\Console\Output\OutputInterface::class),
            $this->createStub(HandlerInterface::class),
        );

        $this->processRunner = new ProcessRunner($logger);
        $this->host = new Localhost('test-host');
    }

    protected function tearDown(): void
    {
        unset($this->deployer);
    }

    public function testRunReturnsOutput(): void
    {
        $params = new RunParams(shell: 'bash -s');
        $result = $this->processRunner->run($this->host, 'echo hello', $params);
        self::assertSame("hello\n", $result);
    }

    public function testRunWithEnvPrependsExport(): void
    {
        $params = new RunParams(
            shell: 'bash -s',
            env: ['MY_VAR' => 'my_value'],
        );
        $result = $this->processRunner->run($this->host, 'echo $MY_VAR', $params);
        self::assertStringContainsString('my_value', $result);
    }

    public function testRunThrowsRunExceptionOnFailure(): void
    {
        $params = new RunParams(shell: 'bash -s');
        $this->expectException(RunException::class);
        $this->processRunner->run($this->host, 'exit 1', $params);
    }

    public function testRunReturnsEmptyStringWithNothrow(): void
    {
        $params = new RunParams(shell: 'bash -s', nothrow: true);
        $result = $this->processRunner->run($this->host, 'exit 1', $params);
        self::assertSame('', $result);
    }

    public function testRunWithDotenv(): void
    {
        $dotenvPath = sys_get_temp_dir() . '/deployer_test_dotenv';
        file_put_contents($dotenvPath, 'export DOTENV_VAR=dotenv_value');

        try {
            $params = new RunParams(
                shell: 'bash -s',
                dotenv: $dotenvPath,
            );
            $result = $this->processRunner->run($this->host, 'echo $DOTENV_VAR', $params);
            self::assertStringContainsString('dotenv_value', $result);
        } finally {
            unlink($dotenvPath);
        }
    }
}
