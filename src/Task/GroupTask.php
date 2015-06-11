<?php

/*
 * (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

/**
 * Group task
 */
class GroupTask extends Task
{
    /**
     * Yes, it is constructor.
     *
     * @param string $name The name of group task
     */
    public function __construct($name)
    {
        $this->name = $name;
        // Attention: not calls parent, because this task can not runs
    }

    /**
     * {@inheritDoc}
     */
    public function run(Context $context)
    {
        throw new \RuntimeException('Group task should never be running.');
    }
}
