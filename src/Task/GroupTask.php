<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

class GroupTask extends AbstractTask
{
    /**
     * @var TaskInterface[]
     */
    private $tasks;

    /**
     * @param TaskInterface[] $tasks
     */
    public function __construct($tasks)
    {
        $this->tasks = $tasks;
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        foreach ($this->tasks as $task) {
            $task->run();
        }
    }
}