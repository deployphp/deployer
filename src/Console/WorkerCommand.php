<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Deployer\Console\Output\RemoteOutput;
use Deployer\Deployer;
use Deployer\Exception\NonFatalException;
use Deployer\Task\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WorkerCommand extends Command
{
    /**
     * @var Deployer
     */
    private $deployer;

    /**
     * @param Deployer $deployer
     */
    public function __construct(Deployer $deployer)
    {
        parent::__construct('worker');
        $this->setDescription('Deployer uses workers for parallel deployment');
        $this->setHidden(true);
        $this->deployer = $deployer;
        $this->addOption(
            'hostname',
            null,
            InputOption::VALUE_REQUIRED
        );
        $this->addOption(
            'task',
            null,
            InputOption::VALUE_REQUIRED
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $hostname = $input->getOption('hostname');
        $host = $this->deployer->hosts->get($hostname);

        $task = $input->getOption('task');
        $task = $this->deployer->tasks->get($task);

        $informer = $this->deployer->informer;

        if ($task->shouldBePerformed($host)) {
            try {
                $task->run(new Context($host, $input, $output));
            } catch (NonFatalException $exception) {
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
