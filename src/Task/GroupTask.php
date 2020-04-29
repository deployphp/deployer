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
     *
     * @var string[]
     */
    private $group;

    /**
     * @param string $name
     * @param string[] $group
     */
    public function __construct($name, $group)
    {
        parent::__construct($name);
        $this->group = $group;
    }

    public function run(Context $context)
    {
        // TODO: implement;
    }

    /**
     * List of dependent tasks names
     *
     * @return string[]
     */
    public function getGroup()
    {
        return $this->group;
    }
}
