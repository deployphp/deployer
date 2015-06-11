<?php

/**
 * (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Executor;

use Deployer\Server\ServerInterface;
use Deployer\Server\Environment;
use Deployer\Task\Task;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * All executors should implement this interface
 */
interface ExecutorInterface
{
    /**
     * Run task
     *
     * @param Task[]            $tasks
     * @param ServerInterface[] $servers
     * @param Environment[]     $environments
     * @param InputInterface    $input
     * @param OutputInterface   $output
     */
    public function run($tasks, $servers, $environments, $input, $output);
}
