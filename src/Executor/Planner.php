<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Executor;

use Deployer\Exception\Exception;
use Deployer\Exception\RunException;
use Deployer\Host\Host;
use Deployer\Task\Task;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class Planner
{
    /**
     * @var Table
     */
    private $table;

    private $template;

    /**
     * Planner constructor.
     *
     * @param OutputInterface $output
     * @param Host[] $hosts
     */
    public function __construct(OutputInterface $output, $hosts)
    {
        $headers = [];
        $this->template = [];
        foreach ($hosts as $host) {
            $headers[] = $host->tag();
            $this->template[] = $host->alias();
        }
        $this->table = new Table($output);
        $this->table->setHeaders($headers);
        $this->table->setStyle('box');
    }

    /**
     * @param Host[] $hosts
     * @param Task $task
     */
    public function commit(array $hosts, Task $task)
    {
        $row = [];
        foreach ($this->template as $alias) {
            $on = "-";
            foreach ($hosts as $host) {
                if ($alias === $host->alias()) {
                    $on = $task->getName();
                    break;
                }
            }
            $row[] = $on;
        }
        $this->table->addRow($row);
    }

    public function render()
    {
        $this->table->render();
    }
}
