<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use Deployer\Exception\Exception;
use function Deployer\task;

class GroupTask extends Task
{
    /**
     * List of tasks
     *
     * @var array
     */
    private $group;

    /**
     * @param string $name
     * @param array $group
     */
    public function __construct($name, $group)
    {
        parent::__construct($name);
        $this->group = $group;
    }

    /**
     * {@inheritdoc}
     */
    public function run(Context $context)
    {
        throw new \RuntimeException("Can't run group task.");
    }

    /**
     * List of dependent tasks names
     *
     * @return array
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @inheritdoc
     */
    public function onCondition(...$conditions)
    {
        foreach ($this->getGroup() as $task) {
            call_user_func_array([task($task), 'onCondition'], $conditions);
        }
    }

    public function local()
    {
        throw new Exception('Group tasks can\'t be local.');
    }
}
