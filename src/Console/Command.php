<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Deployer\Deployer;
use Deployer\Server\Current;
use Deployer\TaskInterface;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends BaseCommand
{
    /**
     * @var TaskInterface
     */
    private $task;

    /**
     * @param null|string $name
     * @param TaskInterface $task
     */
    public function __construct($name, TaskInterface $task)
    {
        parent::__construct($name);
        $this->task = $task;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach (Deployer::$servers as $name => $server) {
            Current::setServer($name, $server);
            $this->task->run();
        }
    }
}