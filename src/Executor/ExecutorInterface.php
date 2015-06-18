<?php
/* (c) Anton Medvedev <anton@elfet.ru>
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

interface ExecutorInterface
{
    /**
     * @param \Deployer\Task\Task[] $tasks
     * @param \Deployer\Server\ServerInterface[] $servers
     * @param \Deployer\Server\Environment[] $environments
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function run($tasks, $servers, $environments, $input, $output);
}
