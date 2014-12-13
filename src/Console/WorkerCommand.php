<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Pure\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WorkerCommand extends Command
{
    public function __construct()
    {
        parent::__construct('worker');

        $this->addOption(
            'master',
            null,
            InputOption::VALUE_REQUIRED
        );
        
        $this->addOption(
            'server',
            null,
            InputOption::VALUE_REQUIRED
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        list($server, $port) = explode(':', $input->getOption('master'));
        
        $pure = new Client($port, $server);
        
        $pure->queue($input->getOption('server') . '_output')->enqueue("Worker on server: " . $input->getOption('server'));
        
        $i = 100;
        while($i--) {
            sleep(1);
            $pure->queue($input->getOption('server') . '_output')->enqueue("$i");
        }
    }
}
