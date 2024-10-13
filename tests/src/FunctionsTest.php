<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Configuration\Configuration;
use Deployer\Host\Host;
use Deployer\Host\Localhost;
use Deployer\Task\Context;
use Deployer\Task\GroupTask;
use Deployer\Task\Task;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;

use function Deployer\localhost;

class FunctionsTest extends TestCase
{
    /**
     * @var Deployer
     */
    private $deployer;

    protected function setUp(): void
    {
        $console = new Application();

        $input = $this->createMock(Input::class);
        $output = $this->createMock(Output::class);
        $host = new Localhost();

        $this->deployer = new Deployer($console);
        $this->deployer['input'] = $input;
        $this->deployer['output'] = $output;
        Context::push(new Context($host));
    }

    protected function tearDown(): void
    {
        Context::pop();
        unset($this->deployer);
        $this->deployer = null;
    }

    public function testHost()
    {
        host('domain.com');
        self::assertInstanceOf(Host::class, $this->deployer->hosts->get('domain.com'));

        host('a1.domain.com', 'a2.domain.com')->set('roles', 'app');
        self::assertInstanceOf(Host::class, $this->deployer->hosts->get('a1.domain.com'));
        self::assertInstanceOf(Host::class, $this->deployer->hosts->get('a2.domain.com'));

        host('db[1:2].domain.com')->set('roles', 'db');
        self::assertInstanceOf(Host::class, $this->deployer->hosts->get('db1.domain.com'));
        self::assertInstanceOf(Host::class, $this->deployer->hosts->get('db2.domain.com'));
    }

    public function testLocalhost()
    {
        localhost('domain.com');
        self::assertInstanceOf(Localhost::class, $this->deployer->hosts->get('domain.com'));
    }

    public function testTask()
    {
        task('task', function () {});

        $task = $this->deployer->tasks->get('task');
        self::assertInstanceOf(Task::class, $task);

        $task = task('task');
        self::assertInstanceOf(Task::class, $task);

        task('group', ['task']);
        $task = $this->deployer->tasks->get('group');
        self::assertInstanceOf(GroupTask::class, $task);
    }

    public function testBefore()
    {
        task('main', function () {});
        task('before', function () {});
        before('main', 'before');
        before('before', function () {});

        $names = $this->taskToNames($this->deployer->scriptManager->getTasks('main'));
        self::assertEquals(['before:before', 'before', 'main'], $names);
    }

    public function testAfter()
    {
        task('main', function () {});
        task('after', function () {});
        after('main', 'after');
        after('after', function () {});

        $names = $this->taskToNames($this->deployer->scriptManager->getTasks('main'));
        self::assertEquals(['main', 'after', 'after:after'], $names);
    }

    public function testRunLocally()
    {
        $output = runLocally('echo "hello"');
        self::assertEquals('hello', $output);
    }

    public function testRunLocallyWithOptions()
    {
        Context::get()->getConfig()->set('env', ['DEPLOYER_ENV' => 'default', 'DEPLOYER_ENV_TMP' => 'default']);

        $output = runLocally('echo $DEPLOYER_ENV');
        self::assertEquals('default', $output);
        $output = runLocally('echo $DEPLOYER_ENV_TMP');
        self::assertEquals('default', $output);

        $output = runLocally('echo $DEPLOYER_ENV', ['env' => ['DEPLOYER_ENV_TMP' => 'overwritten']]);
        self::assertEquals('default', $output);
        $output = runLocally('echo $DEPLOYER_ENV_TMP', ['env' => ['DEPLOYER_ENV_TMP' => 'overwritten']]);
        self::assertEquals('overwritten', $output);
    }

    public function testWithinSetsWorkingPaths()
    {
        Context::get()->getConfig()->set('working_path', '/foo');

        within('/bar', function () {
            $withinWorkingPath = Context::get()->getConfig()->get('working_path');
            self::assertEquals('/bar', $withinWorkingPath);
        });

        $originalWorkingPath = Context::get()->getConfig()->get('working_path');
        self::assertEquals('/foo', $originalWorkingPath);
    }

    public function testWithinRestoresWorkingPathInCaseOfException()
    {
        Context::get()->getConfig()->set('working_path', '/foo');

        try {
            within('/bar', function () {
                throw new \Exception('Dummy exception');
            });
        } catch (\Exception $exception) {
            // noop
        }

        $originalWorkingPath = Context::get()->getConfig()->get('working_path');
        self::assertEquals('/foo', $originalWorkingPath);
    }

    public function testWithinReturningValue()
    {
        $output = within('/foo', function () {
            return 'bar';
        });

        self::assertEquals('bar', $output);
    }

    public function testWithinWithVoidFunction()
    {
        $output = within('/foo', function () {
            // noop
        });

        self::assertNull($output);
    }

    private function taskToNames($tasks)
    {
        return array_map(function (Task $task) {
            return $task->getName();
        }, $tasks);
    }
}
