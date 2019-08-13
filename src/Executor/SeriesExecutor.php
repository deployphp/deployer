<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Executor;

use Deployer\Console\Output\Informer;
use Deployer\Exception\NonFatalException;
use Deployer\Host\Localhost;
use Deployer\Task\Context;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SeriesExecutor implements ExecutorInterface
{
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var Informer
     */
    private $informer;

    public function __construct(
        InputInterface $input,
        OutputInterface $output,
        Informer $informer
    ) {
        $this->input = $input;
        $this->output = $output;
        $this->informer = $informer;
    }

    /**
     * {@inheritdoc}
     */
    public function run(array $tasks, array $hosts)
    {
        $localhost = new Localhost();
        foreach ($tasks as $task) {
            $success = true;
            $this->informer->startTask($task);

            if ($task->isLocal()) {
                $task->run(new Context($localhost, $this->input, $this->output));
            } else {
                foreach ($hosts as $host) {
                    if ($task->shouldBePerformed($host)) {
                        try {
                            $task->run(new Context($host, $this->input, $this->output));
                        } catch (NonFatalException $exception) {
                            $success = false;
                            $this->informer->taskException($exception, $host);
                        }
                        $this->informer->endOnHost($host->getHostname());
                    }
                }
            }

            if ($success) {
                $this->informer->endTask($task);
            } else {
                $this->informer->taskError();
            }
        }
    }
}
