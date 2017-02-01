<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Deployer\Deployer;
use Deployer\Exception\GracefulShutdownException;
use Deployer\Executor\ExecutorInterface;
use Deployer\Executor\ParallelExecutor;
use Deployer\Executor\SeriesExecutor;
use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Input\InputOption as Option;
use Symfony\Component\Console\Output\OutputInterface as Output;

class TaskCommand extends Command
{
    /**
     * @var Deployer
     */
    private $deployer;

    /**
     * @var ExecutorInterface
     */
    public $executor;

    /**
     * @param string $name
     * @param string $description
     * @param Deployer $deployer
     */
    public function __construct($name, $description, Deployer $deployer)
    {
        parent::__construct($name);
        $this->setDescription($description);
        $this->deployer = $deployer;
    }

    /**
     * Configures the command
     */
    protected function configure()
    {
        $this->addOption(
            'parallel',
            'p',
            Option::VALUE_NONE,
            'Run tasks in parallel.'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(Input $input, Output $output)
    {
        $stage = $input->hasArgument('stage') ? $input->getArgument('stage') : null;

        $tasks = $this->deployer->getScriptManager()->getTasks($this->getName(), $stage);
        $servers = $this->deployer->getStageStrategy()->getServers($stage);
        $environments = iterator_to_array($this->deployer->environments);

        // Validation
        $sshType = \Deployer\get('ssh_type');
        if ($sshType !== 'native') {
            $output->write(
                "<comment>Warning: ssh type `$sshType` will be deprecated in Deployer 5.\n" .
                "Add this lines to your deploy.php file:\n" .
                "\n" .
                "    <fg=white>set(<fg=cyan>'ssh_type'</fg=cyan>, <fg=cyan>'native'</fg=cyan>);\n" .
                "    set(<fg=cyan>'ssh_multiplexing'</fg=cyan>, <fg=magenta;options=bold>true</fg=magenta;options=bold>);</fg=white>\n" .
                "\n" .
                "More info here: https://goo.gl/ya8rKW" .
                "</comment>\n"
            );
        }

        if (isset($this->executor)) {
            $executor = $this->executor;
        } else {
            if ($input->getOption('parallel')) {
                $executor = new ParallelExecutor($this->deployer->getConsole()->getUserDefinition());
            } else {
                $executor = new SeriesExecutor();
            }
        }

        try {
            $executor->run($tasks, $servers, $environments, $input, $output);
        } catch (\Exception $exception) {
            \Deployer\logger($exception->getMessage(), Logger::ERROR);

            if (!($exception instanceof GracefulShutdownException)) {
                // Check if we have tasks to execute on failure.
                if ($this->deployer['onFailure']->has($this->getName())) {
                    $taskName = $this->deployer['onFailure']->get($this->getName());
                    $tasks = $this->deployer->getScriptManager()->getTasks($taskName, $stage);
                    $executor->run($tasks, $servers, $environments, $input, $output);
                }
            }

            throw $exception;
        }

        if (Deployer::hasDefault('terminate_message')) {
            $output->writeln(Deployer::getDefault('terminate_message'));
        }
    }
}
