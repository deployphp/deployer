<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use Deployer\Deployer;
use Deployer\Task;

class TaskFactory
{
    /**
     * Create task.
     * @param callable|string|array $body Code of task or name of other task or array of other tasks.
     * @param string $name Task name.
     * @return TaskInterface
     */
    public static function create($body, $name = null)
    {
        if ($body instanceof \Closure) {

            return new Task($body, $name);

        } elseif (is_string($body)) {

            return new ReferenceTask(Deployer::get()->getTask($body));

        } elseif (is_array($body)) {

            return new GroupTask(array_map(function ($body) {
                return self::create($body);
            }, $body));

        } else {
            throw new \InvalidArgumentException("Task can be an closure or string or array of other tasks names.");
        }

    }
} 