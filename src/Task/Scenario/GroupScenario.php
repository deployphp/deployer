<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task\Scenario;

use Deployer\Task\Scenario\Scenario;

class GroupScenario extends Scenario
{
    /**
     * @var \Deployer\Task\Scenario\Scenario[]
     */
    private $group;

    /**
     * @param \Deployer\Task\Scenario\Scenario[] $group
     */
    public function __construct(array $group)
    {
        parent::__construct(null);
        $this->group = $group;
    }

    /**
     * {@inheritdoc}
     */
    public function getTasks()
    {
        $tasks = [];
        foreach ($this->group as $scenario) {
            $tasks = array_merge($tasks, $scenario->getTasks());
        }
        return array_merge(
            $this->getBefore(),
            $tasks,
            $this->getAfter()
        );
    }
}
