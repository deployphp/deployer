<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Executor;

use Deployer\Console\Output\OutputWatcher;
use Deployer\Server\Environment;
use Deployer\Server\Local;
use Deployer\Task\Context;
use Deployer\Exception\NonFatalException;

class SeriesExecutor implements ExecutorInterface
{
    /**
     * {@inheritdoc}
     */
    public function run($tasks, $servers, $environments, $input, $output)
    {
        $output = new OutputWatcher($output);
        $informer = new Informer($output);
        $localhost = new Local();
        $localEnv = new Environment();

        foreach ($tasks as $task) {
            $success = true;
            $informer->startTask($task->getName());

            if ($task->isOnce()) {
                $task->run(new Context($localhost, $localEnv, $input, $output));
            } else {
                foreach ($servers as $serverName => $server) {
                    if ($task->isOnServer($serverName)) {
                        $env = isset($environments[$serverName]) ? $environments[$serverName] : $environments[$serverName] = new Environment();

                        try {
                            $task->run(new Context($server, $env, $input, $output));
                        } catch (NonFatalException $exception) {
                            $success = false;
                            $informer->taskException($serverName, 'Deployer\Exception\NonFatalException', $exception->getMessage());
                        }

                        $informer->endOnServer($serverName);
                    }
                }
            }

            if ($success) {
                $informer->endTask();
            } else {
                $informer->taskError();
            }
        }
    }
}
