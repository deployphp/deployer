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
    public function get()
    {
        $runners = [];
        foreach ($this->tasks as $task) {
            $runners = array_merge($runners, $task->get());
        }

        return array_merge(
            $this->getBefore(),
            $runners,
            $this->getAfter()
        );
    }
}