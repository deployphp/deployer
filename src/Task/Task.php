<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

class Task
{
    /**
     * Task code.
     * @var callable
     */
    private $callback;

    /**
     * Task description.
     * @var string
     */
    private $description;

    /**
     * @param callable $callback Task code.
     */
    public function __construct(\Closure $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Run task.
     */
    public function run()
    {
        call_user_func($this->callback);
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set task description.
     * @param string $description
     * @return $this
     */
    public function desc($description)
    {
        $this->description = $description;
        return $this;
    }
}
