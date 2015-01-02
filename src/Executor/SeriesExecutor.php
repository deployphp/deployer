<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Executor;

use Deployer\Console\Output\OutputWatcher;
use Deployer\Server\Environment;
use Deployer\Task\Context;

class SeriesExecutor implements ExecutorInterface
{
    /**
     * {@inheritdoc}
     */
    public function run($tasks, $servers, $environments, $input, $output)
    {
        $output = new OutputWatcher($output);
        $informer = new Informer($output);

        foreach ($tasks as $taskName => $task) {
            $informer->startTask($taskName);

            if ($task->isOnce()) {
                $task->run(new Context(null, null, $input, $output));
            } else {
                foreach ($servers as $serverName => $server) {
                    if ($task->runOnServer($serverName)) {
                        $env = isset($environments[$serverName]) ? $environments[$serverName] : $environments[$serverName] = new Environment();
                        
                        $informer->onServer($serverName);
                        $task->run(new Context($server, $env, $input, $output));
                        $informer->endOnServer($serverName);
                    }
                }
            }

            $informer->endTask();
        }
    }
}
