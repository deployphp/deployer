<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Deployer\Collection\Collection;
use Deployer\Deployer;
use Deployer\Executor\SeriesExecutor;
use Deployer\Host\HostSelector;
use Deployer\Logger\Logger;
use Deployer\Task\ScriptManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\ApplicationTester;

class TaskCommandTest extends TestCase
{
    /**
     * @var Deployer
     */
    protected $deployer;

    /**
     * @var TaskCommand
     */
    protected $command;

    /**
     * @var OutputInterface
     */
    protected $output;

    protected function setUp()
    {
        // Create app tester
        $console = new Application();
        $console->setAutoExit(false);
        $console->setCatchExceptions(false);
        $this->tester = new ApplicationTester($console);

        // Prepare Deployer
        $input = $this->createMock(Input::class);
        $this->output = $this->createMock(Output::class);
        $this->deployer = new Deployer($console, $input, $this->output);
        $this->deployer->hostSelector = $this->createConfiguredMock(HostSelector::class, ['getHosts' => ['production']]);
        $this->deployer->logger = $this->createMock(Logger::class);
        $this->deployer->fail = new Collection();

        // Prepare command
        $this->command = new TaskCommand('deploy', '', $this->deployer);
    }

    protected function tearDown()
    {
        unset($this->deployer);
        unset($this->command);
        unset($this->output);
    }

    public function testCannotSkipSelfTask()
    {
        $this->expectExceptionMessage('Cannot skip the task you are trying to run');

        $input = new StringInput('deploy --skip-task deploy');

        $this->command->run($input, $this->output);
    }

    public function testIgnoreNotExistingTaskToSkip()
    {
        $manager = $this->createConfiguredMock(ScriptManager::class, ['getTasks' => ['first', 'second']]);
        $this->deployer->scriptManager = $manager;
        $input = new StringInput('deploy --skip-task third');

        $executor = $this->createMock(SeriesExecutor::class);
        $executor
            ->expects($this->once())
            ->method('run')
            ->with(
                ['first', 'second'],
                $this->anything()
            );
        $this->deployer->seriesExecutor = $executor;

        $this->command->run($input, $this->output);
    }

    public function skipTasksProvider()
    {
        return [
            'normal' => [
                ['one', 'two', 'three'],
                '--skip-task two',
                ['one', 'three']
            ],
            'duplicate skips' => [
                ['first', 'second', 'last'],
                '--skip-task first --skip-task first',
                ['second', 'last']
            ],
            'multiple' => [
                ['deploy:prepare', 'deploy:update','deploy:build', 'deploy:symlink', 'cleanup'],
                '--skip-task deploy:build --skip-task deploy:symlink --skip-task deploy:prepare',
                ['deploy:update', 'cleanup']
            ]
        ];
    }

    /**
     * @dataProvider skipTasksProvider
     */
    public function testSkipsTasks($tasks, $skipInput, $expected)
    {
        $manager = $this->createConfiguredMock(ScriptManager::class, ['getTasks' => $tasks]);
        $this->deployer->scriptManager = $manager;

        $input = new StringInput('deploy ' . $skipInput);

        $executor = $this->createMock(SeriesExecutor::class);
        $executor
            ->expects($this->once())
            ->method('run')
            ->with(
                $expected,
                $this->anything()
            );
        $this->deployer->seriesExecutor = $executor;

        $this->command->run($input, $this->output);
    }
}
