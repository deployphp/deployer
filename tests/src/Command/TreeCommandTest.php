<?php

declare(strict_types=1);

namespace Deployer\Command;

use Deployer\Deployer;
use Deployer\Task\Task;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Tester\CommandTester;

#[Group('unit')]
class TreeCommandTest extends TestCase
{
    private Deployer $deployer;
    private Application $console;

    protected function setUp(): void
    {
        $this->console = new Application();
        $input = $this->createStub(Input::class);
        $output = $this->createStub(Output::class);

        $this->deployer = new Deployer($this->console);
        $this->deployer['input'] = $input;
        $this->deployer['output'] = $output;
    }

    protected function tearDown(): void
    {
        unset($this->deployer);
    }

    public function testTreeForSimpleTask(): void
    {
        $this->deployer->tasks->set('deploy', new Task('deploy', function () {}));

        $command = new TreeCommand($this->deployer);
        $this->console->addCommand($command);

        $tester = new CommandTester($command);
        $tester->execute(['task' => 'deploy']);

        $display = $tester->getDisplay();
        self::assertStringContainsString('deploy', $display);
        self::assertStringContainsString('task-tree', $display);
    }

    public function testTreeForTaskWithBeforeAndAfter(): void
    {
        $mainTask = new Task('deploy', function () {});
        $beforeTask = new Task('prepare', function () {});
        $afterTask = new Task('cleanup', function () {});

        $mainTask->addBefore('prepare');
        $mainTask->addAfter('cleanup');

        $this->deployer->tasks->set('deploy', $mainTask);
        $this->deployer->tasks->set('prepare', $beforeTask);
        $this->deployer->tasks->set('cleanup', $afterTask);

        $command = new TreeCommand($this->deployer);
        $this->console->addCommand($command);

        $tester = new CommandTester($command);
        $tester->execute(['task' => 'deploy']);

        $display = $tester->getDisplay();
        self::assertStringContainsString('deploy', $display);
        self::assertStringContainsString('prepare', $display);
        self::assertStringContainsString('cleanup', $display);
        self::assertStringContainsString('before deploy', $display);
        self::assertStringContainsString('after deploy', $display);
    }
}
