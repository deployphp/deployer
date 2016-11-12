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
        throw new \RuntimeException('Group task should never be running.');
    }

    /**
     * {@inheritdoc}
     */
    public function once()
    {
        throw new \RuntimeException('Group task can not be called once.');
    }

    /**
     * {@inheritdoc}
     */
    public function onlyOn($servers = [])
    {
        throw new \RuntimeException('Group task can not be called only on specified servers.');
    }

    /**
     * List of tasks names.
     *
     * @return array
     */
    public function getTasks()
    {
        return array_merge($this->getBefore(), $this->group, $this->getAfter());
    }
}
