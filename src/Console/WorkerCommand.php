<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Deployer\Collection\PersistentCollection;
use Deployer\Deployer;
use Deployer\Exception\Exception;
use Deployer\Exception\GracefulShutdownException;
use Deployer\Exception\NonFatalException;
use Deployer\Task\Context;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption as Option;
use Symfony\Component\Console\Output\OutputInterface;

class WorkerCommand extends MainCommand
{
    public function __construct(Deployer $deployer)
    {
        parent::__construct('worker', null, $deployer);
        $this->setHidden(true);
    }

    protected function configure()
    {
        $this->addArgument('worker-task', InputArgument::REQUIRED);
        $this->addArgument('worker-host', InputArgument::REQUIRED);
        $this->addArgument('config-directory', InputArgument::REQUIRED);
        $this->addArgument('original-task', InputArgument::REQUIRED);
        $this->addOption('decorated', null, Option::VALUE_NONE);
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->deployer->input = $input;
        $this->deployer->output = $output;

        $output->setDecorated($input->getOption('decorated'));
        if (!$output->isDecorated() && !defined('NO_ANSI')) {
            define('NO_ANSI', 'true');
        }

        $host = $this->deployer->hosts->get($input->getArgument('worker-host'));
        $task = $this->deployer->tasks->get($input->getArgument('worker-task'));

        $this->deployer->config->set('config_directory', $input->getArgument('config-directory'));
        $host->getConfig()->load();

        foreach ($host->getConfig() as $name => $value) {
            $this->deployer->config->set($name, $value);
        }

        try {
            Exception::setTaskSourceLocation($task->getSourceLocation());
            $task->run(new Context($host, $input, $output));

            if ($task->getName() !== 'connect') {
                $this->deployer->messenger->endOnHost($host);
            }
            $host->getConfig()->save();
            return 0;
        } catch (GracefulShutdownException $e) {
            $this->deployer->messenger->renderException($e, $host);
            return GracefulShutdownException::EXIT_CODE;
        } catch (\Throwable $e) {
            $this->deployer->messenger->renderException($e, $host);
            return 255;
        }
    }
}
