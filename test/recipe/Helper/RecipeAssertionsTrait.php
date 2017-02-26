<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Helper;

use Deployer\Task\GroupTask;

trait RecipeAssertionsTrait
{
    /**
     * @param string $envParameterName
     * @param mixed $expectedValue
     */
    public function assertEnvParameterEquals($envParameterName, $expectedValue)
    {
        $this->assertEquals($expectedValue, $this->getEnv($envParameterName));
    }

    /**
     * @param string|array $taskName or array of task names.
     */
    public function assertTaskIsDefined($taskName)
    {
        $tasks = is_array($taskName) ? $taskName : [$taskName];
        foreach ($tasks as $task) {
            $this->assertTrue($this->getTasks()->has($task));
        }
    }

    /**
     * @param string $groupTaskName
     * @param int $stepsNumber
     */
    public function assertGroupTaskStepsNumberEquals($groupTaskName, $stepsNumber)
    {
        $this->assertTaskIsDefined($groupTaskName);

        $groupTask = $this->getTasks()->get($groupTaskName);

        $this->assertInstanceOf(GroupTask::class, $groupTask);
        $this->assertCount($stepsNumber, $groupTask->getTasks());
    }
}