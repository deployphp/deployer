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
     * Should run this task only once and locally?
     * @var bool
     */
    private $once = false;

    /**
     * List of servers names there this task should be executed.
     * @var array  Key contains server names.
     */
    private $onlyOn = [];

    /**
     * Make task internal and not visible in CLI. 
     * @var bool
     */
    private $private = false;

    /**
     * @param callable $callback Task code.
     */
    public function __construct(\Closure $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Run task.
     * 
     * @param Context $context
     */
    public function run(Context $context)
    {
        Context::push($context);
        call_user_func($this->callback);
        Context::pop();
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

    /**
     * Set this task local and run only once.
     * @return $this
     */
    public function once()
    {
        $this->once = true;
        return $this;
    }

    /**
     * @return bool
     */
    public function isOnce()
    {
        return $this->once;
    }

    /**
     * @param array $servers
     * @return $this
     */
    public function onlyOn($servers)
    {
        $this->onlyOn = array_flip($servers);
        return $this;
    }

    /**
     * @return array
     */
    public function getOnlyOn()
    {
        return $this->onlyOn;
    }
    
    /**
     * Decide to run or not to run on this server.
     * @param string $serverName
     * @return bool
     */
    public function runOnServer($serverName)
    {
        if (empty($this->onlyOn)) {
            return true;
        } else {
            return array_key_exists($serverName, $this->onlyOn);
        }
    }

    /**
     * @return boolean
     */
    public function isPrivate()
    {
        return $this->private;
    }

    /**
     * Mark task as private.
     */
    public function setPrivate()
    {
        $this->private = true;
    }
}
