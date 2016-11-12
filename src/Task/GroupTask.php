<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

class GroupTask extends Task
{
    /**
     * List of tasks.
     * @var array
     */
    private $group;

    /**
     * @param string $name Tasks name
     * @param string $group
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
        throw new \RuntimeException('Group task should never be running.');
    }

    /**
     *
     */
    public function getTasks()
    {
        return array_merge($this->getBefore(), $this->group, $this->getAfter());
    }
}
