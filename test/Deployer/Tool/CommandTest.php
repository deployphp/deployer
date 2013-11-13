<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Tool;

class CommandTest extends \PHPUnit_Framework_TestCase
{
    public function testRunTask()
    {
        $task = $this->getMock('Deployer\Task', array(), array('name', 'desc', function () {
        }));

        $task
            ->expects($this->once())
            ->method('run');

        $task
            ->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('name'));


        $command = new Command($task);

        $command->run(
            $this->getMock('Symfony\Component\Console\Input\InputInterface'),
            $this->getMock('Symfony\Component\Console\Output\OutputInterface')
        );
    }
}
 