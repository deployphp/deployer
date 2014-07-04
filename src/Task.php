<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

class Task implements TaskInterface
{
    /**
     * Callable body of current task.
     * @var callable
     */
    private $callback;

    /**
     * @param callable $callback
     */
    public function __construct(\Closure $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Run callback of current task.
     */
    public function run()
    {
        call_user_func($this->callback);
    }
}