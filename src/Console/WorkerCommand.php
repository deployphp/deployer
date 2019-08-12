<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Deployer\Collection\PersistentCollection;
use Deployer\Deployer;
use Deployer\Exception\GracefulShutdownException;
use Deployer\Exception\NonFatalException;
use Deployer\Task\Context;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption as Option;
use Symfony\Component\Console\Output\OutputInterface;

class WorkerCommand extends TaskCommand
{
    public function __construct(Deployer $deployer)
    {
        parent::__construct('worker', null, $deployer);
        $this->deployer = $deployer;
        $this->setHidden(true);
        $this->addArgument('worker-task', InputArgument::REQUIRED);
        $this->addArgument('worker-host', InputArgument::REQUIRED);
        $this->addArgument('worker-config', InputArgument::REQUIRED);
        $this->addArgument('original-task', InputArgument::REQUIRED);
        $this->addOption('decorated', null, Option::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setDecorated($input->getOption('decorated'));
        if (!$output->isDecorated()) {
            define('NO_ANSI', 'true');
        }

        $host = $this->deployer->hosts->get($input->getArgument('worker-host'));
        $task = $this->deployer->tasks->get($input->getArgument('worker-task'));

        $persistentCollection = new PersistentCollection($input->getArgument('worker-config'));
        $persistentCollection->load();

        $host->getConfig()->setCollection($persistentCollection);
        foreach ($persistentCollection as $name => $value) {
            $this->deployer->config->set($name, $value);
        }

        try {
            $task->run(new Context($host, $input, $output));
            $this->deployer->informer->endOnHost($host);

            $persistentCollection->flush();

            return 0;
        } catch (GracefulShutdownException $e) {
            $this->deployer->informer->taskException($e, $host);
            return GracefulShutdownException::EXIR_CODE;
        } catch (\Throwable $e) {
            $this->deployer->informer->taskException($e, $host);
            return 255;
        }
    }
}
