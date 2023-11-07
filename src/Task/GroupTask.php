<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use function Deployer\invoke;

class GroupTask extends Task
{
    /**
     * List of tasks.
     *
     * @var string[]
     */
    private $group;

    /**
     * @param string[] $group
     */
    public function __construct(string $name, array $group)
    {
        parent::__construct($name);
        $this->group = $group;
    }

    public function run(Context $context): void
    {
        foreach ($this->group as $item) {
            invoke($item);
        }
    }

    /**
     * List of dependent tasks names
     *
     * @return string[]
     */
    public function getGroup(): array
    {
        return $this->group;
    }

    public function setGroup(array $group): void
    {
        $this->group = $group;
    }
}
