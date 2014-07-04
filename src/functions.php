<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Deployer\Deployer;
use Deployer\Task;

/**
 * Define a new task and save to tasks list.
 * @param string $name Name of current task.
 * @param callable|array $callback Callable task or array of names of other tasks.
 */
function task($name, $callback)
{
    if (is_callable($callback)) {
        Task::create($name, $callback);
    } elseif (is_array($callback)) {

    } else {
        throw new \InvalidArgumentException("Task can be an closure or array of other tasks names.");
    }
}

/**
 * Writes a message to the output and adds a newline at the end.
 * @param string $message
 */
function writeln($message)
{
    Deployer::get()->getOutput()->writeln($message);
}

/**
 * Writes a message to the output.
 * @param string $message
 */
function write($message)
{
    Deployer::get()->getOutput()->write($message);
}