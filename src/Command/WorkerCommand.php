<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Command;

use Deployer\Deployer;
use Deployer\Executor\Worker;
use Deployer\Host\Localhost;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption as Option;
use Symfony\Component\Console\Output\OutputInterface;
use function Deployer\localhost;

class WorkerCommand extends MainCommand
{
    public function __construct(Deployer $deployer)
    {
        parent::__construct('worker', null, $deployer);
        $this->setHidden(true);
    }

    protected function configure()
    {
        parent::configure();
        $this->addOption('task', null, Option::VALUE_REQUIRED);
        $this->addOption('host', null, Option::VALUE_REQUIRED);
        $this->addOption('port', null, Option::VALUE_REQUIRED);
        $this->addOption('decorated', null, Option::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->deployer->input = $input;
        $this->deployer->output = $output;
        $this->deployer['log'] = $input->getOption('log');
        $output->setDecorated($input->getOption('decorated'));
        if (!$output->isDecorated() && !defined('NO_ANSI')) {
            define('NO_ANSI', 'true');
        }
        $this->deployer->config->set('master_url', 'http://localhost:' . $input->getOption('port'));

        $task = $this->deployer->tasks->get($input->getOption('task'));
        $host = $this->deployer->hosts->get($input->getOption('host'));
        $host->config()->load();

        $worker = new Worker($this->deployer);
        $exitCode = $worker->execute($task, $host);

        $host->config()->save();
        return $exitCode;
    }
}
