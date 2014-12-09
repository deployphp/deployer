<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Executor;

use Deployer\Console\StateOutput;
use Deployer\Server\Environment;
use Deployer\Server\ServerInterface;
use Deployer\Task\Context;
use Deployer\Task\Task;
use Symfony\Component\Console\Output\OutputInterface as Output;

class SeriesExecutor implements ExecutorInterface
{
    /**
     * {@inheritdoc}
     */
    public function run($tasks, $servers, $input, $output)
    {
        $informer = new Informer($output);
        
        $environments = [];

        foreach ($tasks as $taskName => $task) {
            $informer->startTask($taskName);

            if ($task->isOnce()) {
                $task->run(new Context(null, null, $input, $output));
            } else {
                $env = isset($environments[$taskName]) ? $environments[$taskName] : $environments[$taskName] = new Environment();

                foreach ($servers as $serverName => $server) {
                    if ($task->runOnServer($serverName)) {
                        $informer->onServer($serverName);
                        $task->run(new Context($server, $env, $input, $output));
                    }
                }
            }

            $informer->endTask();
        }
    }
}
