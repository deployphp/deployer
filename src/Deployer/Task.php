<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Tool\Command;

class Task
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var callable
     */
    private $callback;

    /**
     * @var bool
     */
    private $private;

    /**
     * @param $name
     * @param $description
     * @param callable $callback
     */
    public function __construct($name, $description, \Closure $callback)
    {
        $this->name = $name;
        $this->description = $description;
        $this->callback = $callback;
        $this->private = false === $description;
    }

    /**
     * Run task.
     */
    public function run()
    {
        call_user_func($this->callback);
    }

    public function createCommand()
    {
        return new Command($this);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return boolean
     */
    public function isPrivate()
    {
        return $this->private;
    }
}