<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Executor;

use Deployer\Console\Output\Informer;
use Deployer\Console\Output\OutputWatcher;
use Deployer\Exception\NonFatalException;
use Deployer\Host\Localhost;
use Deployer\Task\Context;

class SeriesExecutor implements ExecutorInterface
{
    /**
     * {@inheritdoc}
     */
    public function run($tasks, $hosts, $input, $output)
    {
        $output = new OutputWatcher($output);
        $informer = new Informer($output);
        $localhost = new Localhost();

        foreach ($tasks as $task) {
            $success = true;
            $informer->startTask($task->getName());

            if ($task->isLocal()) {
                $task->run(new Context($localhost, $input, $output));
            } else {
                foreach ($hosts as $hostname => $host) {
                    if ($task->isOn($hostname)) {
                        try {
                            $task->run(new Context($host, $input, $output));
                        } catch (NonFatalException $exception) {
                            $success = false;
                            $informer->taskException(
                                $hostname,
                                NonFatalException::class,
                                $exception->getMessage()
                            );
                        }
                        $informer->endOnHost($hostname);
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
