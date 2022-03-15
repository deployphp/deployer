<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Executor;

use Deployer\Host\Host;
use Deployer\Task\Task;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

class Planner
{
    /**
     * @var Table
     */
    private $table;
    /**
     * @var array
     */
    private $template;

    /**
     * Planner constructor.
     *
     * @param Host[] $hosts
     */
    public function __construct(OutputInterface $output, array $hosts)
    {
        $headers = [];
        $this->template = [];
        foreach ($hosts as $host) {
            $headers[] = $host->getTag();
            $this->template[] = $host->getAlias();
        }
        $this->table = new Table($output);
        $this->table->setHeaders($headers);
        $this->table->setStyle('box');
    }

    /**
     * @param Host[] $hosts
     */
    public function commit(array $hosts, Task $task): void
    {
        $row = [];
        foreach ($this->template as $alias) {
            $on = "-";
            foreach ($hosts as $host) {
                if ($alias === $host->getAlias()) {
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
