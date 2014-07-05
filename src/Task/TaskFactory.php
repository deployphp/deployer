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
     * @return TaskInterface
     */
    public static function create($body)
    {
        if ($body instanceof \Closure) {

            return new Task($body);

        } elseif (is_string($body)) {

            if (array_key_exists($body, Deployer::$tasks)) {
                return new ReferenceTask(Deployer::$tasks[$body]);
            } else {
                throw new \RuntimeException("Task \"$body\" does not defined.");
            }

        } elseif (is_array($body)) {

            return new GroupTask(array_map(function ($body) {
                return self::create($body);
            }, $body));

        } else {
            throw new \InvalidArgumentException("Task can be an closure or string or array of other tasks names.");
        }

    }
} 