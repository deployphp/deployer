<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use Deployer\Exception\ConfigurationException;

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
        throw new \RuntimeException('Group task should never be running.');
    }

    /**
     * {@inheritdoc}
     */
    public function local()
    {
        // TODO: Make it possible to create local group of tasks
        throw new \RuntimeException('Group task can not be local.');
    }

    /**
     * {@inheritdoc}
     */
    public function on(...$list)
    {
        throw new ConfigurationException('Group task can not be called only on specified hosts');
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
}
