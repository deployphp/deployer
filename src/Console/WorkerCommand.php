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
use Deployer\Server\Environment;
use Deployer\Task\Context;
use Pure\Client;
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
        if (method_exists($this, 'setHidden')) {
            $this->setHidden(true);
        }
        $this->setDescription('Deployer uses workers for parallel deployment');

        $this->deployer = $deployer;

        $this->addOption(
            'master',
            null,
            InputOption::VALUE_REQUIRED
        );

        $this->addOption(
            'hostname',
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
        list($host, $port) = explode(':', $input->getOption('master'));
        $pure = new Client($port, $host);

        try {
            $host = $this->deployer->hosts->get($hostname);
            $output = new RemoteOutput($output, $pure, $hostname);

            while ($pure->ping()) {
                // Get task to do
                $taskName = $pure->map('tasks_to_do')->get($hostname);

                if (null !== $taskName) {
                    $task = $this->deployer->tasks->get($taskName);

                    try {
                        $task->run(new Context($host, $input, $output));
                    } catch (NonFatalException $e) {
                        $pure->queue('exception')->push([$hostname, get_class($e), $e->getMessage()]);
                    }

                    $pure->map('tasks_to_do')->delete($hostname);
                }
            }
        } catch (\Exception $exception) {
            $pure->queue('exception')->push([$hostname, get_class($exception), $exception->getMessage()]);
        }
    }
}
