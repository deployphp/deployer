<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Deployer\Deployer;
use Deployer\Executor\Worker;
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
        $this->addArgument('master-port', InputArgument::REQUIRED);
        $this->addArgument('original-task', InputArgument::REQUIRED); // added as stringing $input adds own args
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

        $this->deployer->config->set('master_url', 'http://localhost:' . $input->getArgument('master-port'));

        $task = $this->deployer->tasks->get($input->getArgument('worker-task'));
        $host = $this->deployer->hosts->get($input->getArgument('worker-host'));
        $host->config()->load();

        // TODO: Maybe relevant to leave this code. Was here when loading from config file was.
//        foreach ($host->getConfig() as $name => $value) {
//            $this->deployer->config->set($name, $value);
//        }
        $worker = new Worker($this->deployer);
        $exitCode = $worker->execute($task, $host);

        $host->config()->save();
        return $exitCode;
    }
}
