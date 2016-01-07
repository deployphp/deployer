<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task\Scenario;

class Scenario
{
    /**
     * @var string
     */
    private $taskName;

    /**
     * @var Scenario[]
     */
    private $after = [];

    /**
     * @var Scenario[]
     */
    private $before = [];

    /**
     * @param string $taskName
     */
    public function __construct($taskName)
    {
        $this->taskName = $taskName;
    }

    /**
     * @return array
     */
    public function getTasks()
    {
        return array_merge(
            $this->getBefore(),
            [$this->taskName],
            $this->getAfter()
        );
    }

    /**
     * @param Scenario $scenario
     */
    public function addBefore(Scenario $scenario)
    {
        array_unshift($this->before, $scenario);
    }

    /**
     * @param Scenario $scenario
     */
    public function addAfter(Scenario $scenario)
    {
        array_push($this->after, $scenario);
    }

    /**
     * Get before tasks names.
     * @return string[]
     */
    protected function getBefore()
    {
        $tasks = [];
        foreach ($this->before as $scenario) {
            $tasks = array_merge($tasks, $scenario->getTasks());
        }
        return $tasks;
    }

    /**
     * Get after tasks names.
     * @return string[]
     */
    protected function getAfter()
    {
        $tasks = [];
        foreach ($this->after as $scenario) {
            $tasks = array_merge($tasks, $scenario->getTasks());
        }
        return $tasks;
    }
}
