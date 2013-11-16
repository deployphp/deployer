<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Tool\Context;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Tester\ApplicationTester;

class ToolTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var Tool
     */
    private $tool;

    public function testCreate()
    {
        $tool = deployer(false);
        $this->assertInstanceOf('Deployer\Tool', $tool);
    }

    public function testIncludeFunctions()
    {
        deployer();
        $this->assertTrue(function_exists('start'), 'Functions does not included.');
    }

    public function testTask()
    {
        $this->deployer();

        $called = false;
        task('one', 'One task', function () use (&$called) {
            $called = true;
        });

        $this->start('one');

        $this->assertTrue($called, 'Task was not called.');
    }

    public function testTaskCreateWithAndWithoutDesc()
    {
        $this->deployer();

        task('one', function () {
        });

        task('two', 'Task two', function () {
        });

        $this->assertArrayHasKey('one', $this->tool->getTasks());
        $this->assertArrayHasKey('two', $this->tool->getTasks());
    }

    public function testTaskArrayDefinition()
    {
        $this->deployer();

        $calls = array();

        task('one', function () use (&$calls) {
            $calls[] = 'one';
        });

        task('two', function () use (&$calls) {
            $calls[] = 'two';
        });

        task('comp', 'Call one and two', array('one', 'two'));

        $this->start('comp');
        $this->assertContains('one', $calls);
        $this->assertContains('two', $calls);
    }

    public function testConnectCdRunAndUploadFile()
    {
        $localFile = realpath(__DIR__ . '/../fixture/file');

        $remote = $this->getMock('Deployer\Remote\RemoteInterface');
        $remote
            ->expects($this->once())
            ->method('cd')
            ->with('/home');

        $remote
            ->expects($this->once())
            ->method('execute')
            ->with('command');

        $remote
            ->expects($this->once())
            ->method('uploadFile')
            ->with($localFile, '/remote');

        $remoteFactory = $this->getMock('Deployer\Remote\RemoteFactory');
        $remoteFactory
            ->expects($this->once())
            ->method('create')
            ->will($this->returnValue($remote));

        $this->deployer($remoteFactory);

        connect('localhost', 'user', 'password');

        cd('/home');
        run('command');
        upload($localFile, '/remote');
    }

    public function testUploadOfDirectory()
    {
        $local = realpath(__DIR__ . '/../fixture/');

        $remote = $this->getMock('Deployer\Remote\RemoteInterface');

        $remote
            ->expects($this->exactly(2))
            ->method('uploadFile')
            ->with($this->logicalOr(
                $local . '/file', '/remote/file',
                $local . '/src/some.php', '/remote/src/some.php'
            ));

        $remoteFactory = $this->getMock('Deployer\Remote\RemoteFactory');
        $remoteFactory
            ->expects($this->once())
            ->method('create')
            ->will($this->returnValue($remote));

        $this->deployer($remoteFactory);

        connect('localhost', 'user', 'password');
        ignore(array('ignor*'));
        upload($local, '/remote');
    }

    public function testLocal()
    {
        $local = $this->getMock('\Deployer\Utils\Local');
        $local
            ->expects($this->once())
            ->method('execute')
            ->with('command');

        $this->deployer(null, $local);
        runLocally('command');
    }

    public function testGroup()
    {
        $remote1 = $this->getMock('Deployer\Remote\RemoteInterface');
        $remote1
            ->expects($this->exactly(1))
            ->method('execute')
            ->with('command1');

        $remote2 = $this->getMock('Deployer\Remote\RemoteInterface');
        $remote2
            ->expects($this->exactly(2))
            ->method('execute')
            ->with('command2');

        $remoteFactory = $this->getMock('Deployer\Remote\RemoteFactory');
        $remoteFactory
            ->expects($this->any())
            ->method('create')
            ->will($this->returnCallback(function ($host) use ($remote1, $remote2) {
                return $host === 'host1' ? $remote1 : $remote2;
            }));


        $this->deployer($remoteFactory);

        connect('host1', 'user', 'password', 'one');
        connect('host2', 'user', 'password', 'two');
        connect('host3', 'user', 'password', 'two');

        group('one', function () {
            run('command1');
        });

        group('two', function () {
            run('command2');
        });
    }

    public static function setUpBeforeClass()
    {
        include_once __DIR__ . '/../../src/Deployer/functions.php';
    }

    private function deployer($remoteFactory = null, $local = null)
    {
        $this->app = new Application();
        $this->app->setAutoExit(false);
        $this->app->setCatchExceptions(false);

        $this->tool = new Tool(
            $this->app,
            $this->getMock('\Symfony\Component\Console\Input\InputInterface'),
            $this->getMock('\Symfony\Component\Console\Output\OutputInterface'),
            null === $local ? $this->getMock('\Deployer\Utils\Local') : $local,
            null === $remoteFactory ? $this->getMock('Deployer\Remote\RemoteFactory') : $remoteFactory
        );

        Context::push($this->tool);
    }

    protected function tearDown()
    {
        Context::clear();
    }

    private function start($command = null)
    {
        $this->app->addCommands($this->tool->getCommands());
        $app = new ApplicationTester($this->app);

        if (null !== $command) {
            $app->run(array('command' => $command));
        }

        return $app;
    }
}
 