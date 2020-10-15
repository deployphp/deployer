<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Command;

use Deployer\Deployer;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class HostsCommand extends Command
{
    private $deployer;

    public function __construct(Deployer $deployer)
    {
        $this->deployer = $deployer;
        parent::__construct('hosts');
    }

    protected function configure()
    {
        $this->setDescription('Lists hosts');
        $this->addOption('format', null, InputOption::VALUE_OPTIONAL, 'The output format (json, txt)', 'json');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $hosts = [];
        foreach ($this->deployer->hosts as $host) {
            $config = [];
            foreach ($host->config()->ownValues() as $key => $value) {
                $config[$key] = $value;
            }
            $hosts[$host->getAlias()] = $config;
        }

        $format = $input->getOption('format');

        if ($format === 'json') {
            $output->writeln(json_encode($hosts, JSON_PRETTY_PRINT));
        }
        if ($format === 'txt') {
            $txt = [];
            foreach ($hosts as $alias => $host) {
                $txt[] = $alias;
                foreach ($host['labels'] ?? [] as $label => $value) {
                    $txt[] = "$label=$value";
                }
            }
            $output->writeln(array_unique($txt));
        }
        return 0;
    }
}
