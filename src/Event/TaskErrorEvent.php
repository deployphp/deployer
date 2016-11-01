<?php

namespace Deployer\Event;

use Symfony\Component\EventDispatcher\Event;
use Deployer\Task\Task;

/**
 * The order.placed event is dispatched each time an order is created
 * in the system.
 */
class TaskErrorEvent extends Event
{
    /**
     * @var \Deployer\Task\Task
     */
    protected $task;

    /**
     * @var \Exception
     */
    protected $exception;

    /**
     * TaskErrorEvent constructor.
     * @param Task $task
     * @param \Exception|null $exception
     */
    public function __construct(Task $task, \Exception $exception = null)
    {
        $this->task = $task;
        $this->exception = $exception;
    }

    /**
     * @return string
     */
    public function getTaskName()
    {
        return $this->task->getName();
    }

    /**
     * @return \Exception
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        if ($this->exception === null) {
            return 'Unknown error';
        }
        return $this->getException()->getMessage();
    }
}
