<?php

declare(strict_types=1);

namespace Deployer\Executor;

use Deployer\Deployer;
use Deployer\Host\Localhost;
use Deployer\Task\Task;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\Output;

#[Group('unit')]
class PlannerTest extends TestCase
{
    private Deployer $deployer;

    protected function setUp(): void
    {
        $console = new Application();
        $input = $this->createStub(Input::class);
        $output = $this->createStub(Output::class);

        $this->deployer = new Deployer($console);
        $this->deployer['input'] = $input;
        $this->deployer['output'] = $output;
    }

    protected function tearDown(): void
    {
        unset($this->deployer);
    }

    public function testCommitMarksMatchingHosts(): void
    {
        $output = new BufferedOutput();

        $host1 = new Localhost('web');
        $host2 = new Localhost('db');

        $planner = new Planner($output, [$host1, $host2]);

        $task = new Task('deploy', function () {});
        $planner->commit([$host1], $task);
        $planner->render();

        $result = $output->fetch();
        self::assertStringContainsString('deploy', $result);
        self::assertStringContainsString('-', $result);
    }

    public function testCommitAllHosts(): void
    {
        $output = new BufferedOutput();

        $host1 = new Localhost('web');
        $host2 = new Localhost('db');

        $planner = new Planner($output, [$host1, $host2]);

        $task = new Task('deploy', function () {});
        $planner->commit([$host1, $host2], $task);
        $planner->render();

        $result = $output->fetch();
        // Both hosts should show the task name, no "-"
        self::assertStringContainsString('deploy', $result);
    }

    public function testMultipleCommits(): void
    {
        $output = new BufferedOutput();

        $host1 = new Localhost('web');
        $host2 = new Localhost('db');

        $planner = new Planner($output, [$host1, $host2]);

        $task1 = new Task('prepare', function () {});
        $task2 = new Task('deploy', function () {});

        $planner->commit([$host1, $host2], $task1);
        $planner->commit([$host1], $task2);
        $planner->render();

        $result = $output->fetch();
        self::assertStringContainsString('prepare', $result);
        self::assertStringContainsString('deploy', $result);
    }
}
