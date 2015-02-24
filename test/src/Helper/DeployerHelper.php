<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Helper;

use Deployer\Server\EnvironmentCollection;
use Deployer\Server\ServerCollection;
use Deployer\Task\Scenario\ScenarioCollection;
use Deployer\Task\TaskCollection;

trait DeployerHelper
{
    /**
     * @return array
     */
    protected function deployer()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $deployer = $this->getMockBuilder('Deployer\Deployer')->disableOriginalConstructor()->getMock();

        $tasks = [
            'one' => $this->getMockBuilder('Deployer\Task\Task')->disableOriginalConstructor()->getMock(),
            'two' => $this->getMockBuilder('Deployer\Task\Task')->disableOriginalConstructor()->getMock(),
        ];

        $deployer->tasks = new TaskCollection();

        foreach ($tasks as $name => $task) {
            $deployer->tasks[$name] = $task;
        }

        $scenarios = [
            'one' => ['one'],
            'two' => ['two'],
            'all' => ['one', 'two'],
        ];

        $deployer->scenarios = new ScenarioCollection();

        foreach ($scenarios as $name => $scenario) {
            $deployer->scenarios[$name] = $this->getMockBuilder('Deployer\Task\Scenario\Scenario')
                ->disableOriginalConstructor()
                ->getMock();

            $deployer->scenarios[$name]->expects($this->any())
                ->method('getTasks')
                ->will($this->returnValue($scenario));
        }

        $servers = [
            'first' => $this->getMockBuilder('Deployer\Server\ServerInterface')->disableOriginalConstructor()->getMock(),
            'second' => $this->getMockBuilder('Deployer\Server\ServerInterface')->disableOriginalConstructor()->getMock(),
        ];

        $deployer->servers = new ServerCollection();

        foreach ($servers as $name => $server) {
            $deployer->servers[$name] = $server;
        }

        $environments = [
            'first' => $this->getMockBuilder('Deployer\Server\Environment')->disableOriginalConstructor()->getMock(),
            'second' => $this->getMockBuilder('Deployer\Server\Environment')->disableOriginalConstructor()->getMock(),
        ];

        $deployer->environments = new EnvironmentCollection();

        foreach ($environments as $name => $env) {
            $deployer->environments[$name] = $env;
        }
        
        return [$deployer, $tasks, $servers, $environments, $input, $output];
    }
} 