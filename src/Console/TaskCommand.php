<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Deployer\Deployer;
use Deployer\Task\Task;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Input\InputOption as Option;
use Symfony\Component\Console\Output\OutputInterface as Output;

class TaskCommand extends Command
{
    /**
     * @var Deployer
     */
    private $deployer;

    /**
     * @var Task
     */
    private $task;

    /**
     * @param string $name
     * @param Task $task
     * @param Deployer $deployer
     */
    public function __construct($name, Task $task, Deployer $deployer)
    {
        parent::__construct($name);
        $this->deployer = $deployer;
        $this->task = $task;
        $this->setDescription($task->getDescription());

        $this->addOption(
            'server',
            null,
            Option::VALUE_OPTIONAL,
            'Run tasks only on this server or group of servers.'
        );
    }

    protected function execute(Input $input, Output $output)
    {
        
    }
}
