<?php

declare(strict_types=1);

namespace Deployer\Executor;

use Deployer\Deployer;
use Deployer\Host\HostCollection;
use Deployer\Host\Localhost;
use Deployer\Logger\Logger;
use Deployer\Logger\Handler\HandlerInterface;
use Deployer\Task\Task;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;

#[Group('unit')]
class MasterTest extends TestCase
{
    private Deployer $deployer;
    private Logger $logger;

    /** @var string[] Track which hosts each task ran on */
    private array $executedOn = [];

    protected function setUp(): void
    {
        $console = new Application();
        $input = $this->createStub(Input::class);
        $output = $this->createStub(Output::class);

        $this->deployer = new Deployer($console);
        $this->deployer['input'] = $input;
        $this->deployer['output'] = $output;

        $this->logger = new Logger(
            $this->createStub(OutputInterface::class),
            $this->createStub(HandlerInterface::class),
        );
        $this->deployer['logger'] = $this->logger;

        $this->executedOn = [];
    }

    protected function tearDown(): void
    {
        unset($this->deployer);
    }

    private function createMaster(int $limit = 0): Master
    {
        $inputDef = new InputDefinition([
            new InputOption('limit', 'l', InputOption::VALUE_REQUIRED, '', null),
        ]);
        $input = new ArrayInput(
            $limit > 0 ? ['--limit' => $limit] : [],
            $inputDef,
        );
        $output = $this->createStub(OutputInterface::class);
        $hosts = new HostCollection();
        return new Master($hosts, $input, $output, $this->logger);
    }

    private function createTrackingTask(string $name): Task
    {
        $executed = &$this->executedOn;
        return new Task($name, function () use ($name, &$executed) {
            $alias = \Deployer\Task\Context::get()->getHost()->getAlias();
            $executed[] = "$name:$alias";
        });
    }

    public function testRunExecutesTaskOnAllHosts(): void
    {
        $master = $this->createMaster();
        $host1 = new Localhost('web1');
        $host2 = new Localhost('web2');
        $task = $this->createTrackingTask('deploy');

        $exitCode = $master->run([$task], [$host1, $host2]);

        self::assertSame(0, $exitCode);
        self::assertSame(['deploy:web1', 'deploy:web2'], $this->executedOn);
    }

    public function testRunOnceExecutesOnFirstHostOnly(): void
    {
        $master = $this->createMaster();
        $host1 = new Localhost('web1');
        $host2 = new Localhost('web2');
        $task = $this->createTrackingTask('migrate');
        $task->once();

        $exitCode = $master->run([$task], [$host1, $host2]);

        self::assertSame(0, $exitCode);
        self::assertSame(['migrate:web1'], $this->executedOn);
    }

    public function testRunOncePerNodeDeduplicatesByHostname(): void
    {
        $master = $this->createMaster();

        // Two hosts with same hostname but different aliases
        $host1 = new Localhost('app1');
        $host1->setHostname('node-a');
        $host2 = new Localhost('app2');
        $host2->setHostname('node-a');
        $host3 = new Localhost('app3');
        $host3->setHostname('node-b');

        $task = $this->createTrackingTask('migrate');
        $task->oncePerNode();

        $exitCode = $master->run([$task], [$host1, $host2, $host3]);

        self::assertSame(0, $exitCode);
        self::assertCount(2, $this->executedOn);
        self::assertSame('migrate:app1', $this->executedOn[0]);
        self::assertSame('migrate:app3', $this->executedOn[1]);
    }

    public function testRunOncePerNodeDeduplicatesByNodeLabel(): void
    {
        $master = $this->createMaster();

        $host1 = new Localhost('app1');
        $host1->config()->set('labels', ['node' => 'group-a']);
        $host2 = new Localhost('app2');
        $host2->config()->set('labels', ['node' => 'group-a']);
        $host3 = new Localhost('app3');
        $host3->config()->set('labels', ['node' => 'group-b']);

        $task = $this->createTrackingTask('migrate');
        $task->oncePerNode();

        $exitCode = $master->run([$task], [$host1, $host2, $host3]);

        self::assertSame(0, $exitCode);
        self::assertCount(2, $this->executedOn);
        self::assertStringContainsString('app1', $this->executedOn[0]);
        self::assertStringContainsString('app3', $this->executedOn[1]);
    }

    public function testRunWithLimitChunksHosts(): void
    {
        $master = $this->createMaster(limit: 2);

        $hosts = [];
        for ($i = 1; $i <= 4; $i++) {
            $hosts[] = new Localhost("web$i");
        }

        $task = $this->createTrackingTask('deploy');

        $exitCode = $master->run([$task], $hosts);

        self::assertSame(0, $exitCode);
        self::assertCount(4, $this->executedOn);
    }

    public function testRunStopsOnNonZeroExitCode(): void
    {
        $master = $this->createMaster();
        $host1 = new Localhost('web1');
        $host2 = new Localhost('web2');

        $task = new Task('fail', function () {
            throw new \Deployer\Exception\GracefulShutdownException('stop');
        });

        $exitCode = $master->run([$task], [$host1, $host2]);

        self::assertSame(\Deployer\Exception\GracefulShutdownException::EXIT_CODE, $exitCode);
    }

    public function testRunMultipleTasksInSequence(): void
    {
        $master = $this->createMaster();
        $host = new Localhost('web1');
        $task1 = $this->createTrackingTask('prepare');
        $task2 = $this->createTrackingTask('deploy');

        $exitCode = $master->run([$task1, $task2], [$host]);

        self::assertSame(0, $exitCode);
        self::assertSame(['prepare:web1', 'deploy:web1'], $this->executedOn);
    }

    public function testRunWithPlannerCommitsInsteadOfExecuting(): void
    {
        $master = $this->createMaster();
        $output = new BufferedOutput();
        $host1 = new Localhost('web1');
        $host2 = new Localhost('web2');
        $task = $this->createTrackingTask('deploy');

        $planner = new Planner($output, [$host1, $host2]);
        $exitCode = $master->run([$task], [$host1, $host2], $planner);

        self::assertSame(0, $exitCode);
        // Task should NOT have been executed.
        self::assertEmpty($this->executedOn);
    }
}
