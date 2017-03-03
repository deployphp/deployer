<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Console\Application;
use Deployer\Server\Environment;
use Deployer\Task\Context;
use PHPUnit\Framework\TestCase;

class FunctionsTest extends TestCase
{
    /**
     * @var Deployer
     */
    private $deployer;

    /**
     * @var Application
     */
    private $console;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $_input;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $_output;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $_server;

    /**
     * @var Environment
     */
    private $_env;

    protected function setUp()
    {
        $this->console = new Application();

        $this->_input = $this->createMock('Symfony\Component\Console\Input\InputInterface');
        $this->_output = $this->createMock('Symfony\Component\Console\Output\OutputInterface');
        $this->_server = $this->getMockBuilder('Deployer\Server\ServerInterface')->disableOriginalConstructor()->getMock();

        $this->_env = new Environment();
        $this->_env->set("local_path", __DIR__ . '/../fixture/app');
        $this->_env->set("remote_path", "/home/www");

        $this->deployer = new Deployer($this->console, $this->_input, $this->_output);
        Context::push(new Context($this->_server, $this->_env, $this->_input, $this->_output));
    }

    protected function tearDown()
    {
        Context::pop();
        unset($this->deployer);
        $this->deployer = null;
    }

    public function testServer()
    {
        server('main', 'domain.com', 22);

        $server = $this->deployer->servers->get('main');
        $env = $this->deployer->environments->get('main');

        $this->assertInstanceOf('Deployer\Server\ServerInterface', $server);
        $this->assertInstanceOf('Deployer\Server\Environment', $env);
    }

    public function testLocalServer()
    {
        localServer('main')->set('deploy_path', __DIR__ . '/localhost');

        $server = $this->deployer->servers->get('main');
        $env = $this->deployer->environments->get('main');

        $this->assertInstanceOf('Deployer\Server\ServerInterface', $server);
        $this->assertInstanceOf('Deployer\Server\Environment', $env);
        $this->assertEquals(__DIR__ . '/localhost', $env->get('deploy_path'));
    }

    public function testCluster()
    {
        $deployer = $this->deployer;

        cluster('myIstanbulDCCluster', ['192.168.0.1', '192.168.0.2'], 22);

        $server0 = $deployer->servers->get('myIstanbulDCCluster_0');
        $env0 = $deployer->environments->get('myIstanbulDCCluster_0');

        $server1 = $deployer->servers->get('myIstanbulDCCluster_1');
        $env1 = $deployer->environments->get('myIstanbulDCCluster_1');

        $this->assertInstanceOf('Deployer\Server\ServerInterface', $server0);
        $this->assertInstanceOf('Deployer\Server\Environment', $env0);

        $this->assertInstanceOf('Deployer\Server\ServerInterface', $server1);
        $this->assertInstanceOf('Deployer\Server\Environment', $env1);
    }

    public function testServerList()
    {
        serverList(__DIR__ . '/../fixture/servers.yml');

        foreach (['production', 'beta', 'test'] as $stage) {
            $server = $this->deployer->servers->get($stage);
            $env = $this->deployer->environments->get($stage);

            $this->assertInstanceOf('Deployer\Server\ServerInterface', $server);
            $this->assertInstanceOf('Deployer\Server\Environment', $env);

            $this->assertEquals('/home', $env->get('deploy_path'));
        }
    }

    public function testTask()
    {
        task('task', function () {
        });

        $task = $this->deployer->tasks->get('task');
        $this->assertInstanceOf('Deployer\Task\Task', $task);

        $task = task('task');
        $this->assertInstanceOf('Deployer\Task\Task', $task);

        task('group', ['task']);
        $task = $this->deployer->tasks->get('group');
        $this->assertInstanceOf('Deployer\Task\GroupTask', $task);
    }

    public function testBefore()
    {
        task('main', function () {
        });
        task('before', function () {
        });
        before('main', 'before');

        $names = $this->taskToNames($this->deployer->getScriptManager()->getTasks('main'));
        $this->assertEquals(['before', 'main'], $names);
    }

    public function testAfter()
    {
        task('main', function () {
        });
        task('after', function () {
        });
        after('main', 'after');

        $names = $this->taskToNames($this->deployer->getScriptManager()->getTasks('main'));
        $this->assertEquals(['main', 'after'], $names);
    }

    public function testTaskWithoutHooks()
    {
        task('main', function () {});
        task('groupTask', ['main']);
        task('before', function () {});
        task('after', function () {});

        before('main', 'before');
        after('main', 'after');

        before('groupTask', 'before');
        after('groupTask', 'after');

        $names = $this->taskToNames($this->deployer->getScriptManager()->getTasks('main'));
        $this->assertEquals(['before', 'main', 'after'], $names);
        $names = $this->taskToNames($this->deployer->getScriptManager()->getTasks('groupTask'));
        $this->assertEquals(['before', 'before', 'main', 'after', 'after'], $names);

        $this->deployer->getScriptManager()->setHooksEnabled(false);

        $names = $this->taskToNames($this->deployer->getScriptManager()->getTasks('main'));
        $this->assertEquals(['main'], $names);
        $names = $this->taskToNames($this->deployer->getScriptManager()->getTasks('groupTask'));
        $this->assertEquals(['main'], $names);
    }

    private function taskToNames($tasks)
    {
        return array_map(function ($task) {
            return $task->getName();
        }, $tasks);
    }

    public function testRunLocally()
    {
        $output = runLocally('echo "hello"');

        $this->assertInstanceOf('Deployer\Type\Result', $output);
        $this->assertEquals('hello', (string)$output);
    }

    public function testUpload()
    {
        $this->_server
            ->expects($this->atLeastOnce())
            ->method('upload')
            ->with(
                $this->callback(function ($local) {
                    return is_file($local);
                }),
                $this->callback(function ($remote) {
                    return is_file(str_replace('/home/www', __DIR__ . '/../fixture/app', $remote));
                }));

        // Directory
        upload('{{local_path}}', '{{remote_path}}');

        // File
        upload('{{local_path}}/README.md', '{{remote_path}}/README.md');
    }

    public function testDownloadFile()
    {
        $this->_server
            ->expects($this->once())
            ->method('download')
            ->with(
                $this->_env->get("local_path") . "/README.md",
                $this->_env->get("remote_path") . "/README.md"
            );

        download('{{local_path}}/README.md', '{{remote_path}}/README.md');
    }

    public function testDownloadDirectory()
    {
        $this->_server
            ->expects($this->once())
            ->method('download')
            ->with(
                $this->_env->get("local_path"),
                $this->_env->get("remote_path")
            );


        download('{{local_path}}', '{{remote_path}}');
    }
}
