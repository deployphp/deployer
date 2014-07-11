<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Task\AbstractTask;
use Deployer\Task\Runner;

class Task extends AbstractTask
{
    /**
     * Callable body of current task.
     * @var callable
     */
    private $callback;

    /**
     * Now every task will be have a name.
     * @var string
     */
    private $name;

    /**
     * @param callable $callback
     */
    public function __construct(\Closure $callback, $name = null)
    {
        $this->callback = $callback;
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        return array_merge(
            $this->getBefore(),
            [new Runner($this->callback, $this->name, $this->getDescription())],
            $this->getAfter()
        );
    }
}