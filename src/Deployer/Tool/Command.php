<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Tool;

use Deployer\Task;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends BaseCommand
{
    private $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
        parent::__construct($task->getName());
        $this->setDescription($task->getDescription());
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->task->run();
    }
}