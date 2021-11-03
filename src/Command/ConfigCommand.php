<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Command;

use Deployer\Deployer;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Symfony\Component\Yaml\Yaml;

class ConfigCommand extends SelectCommand
{
    public function __construct(Deployer $deployer)
    {
        parent::__construct('config', $deployer);
        $this->setDescription('Get config for hosts');
    }

    protected function configure()
    {
        parent::configure();
        $this->addOption('format', null, InputOption::VALUE_OPTIONAL, 'The output format (json, yaml)', 'json');
    }

    protected function execute(Input $input, Output $output): int
    {
        $hosts = $this->selectHosts($input, $output);
        $config = [];
        foreach ($hosts as $host) {
            $config[$host->getAlias()] = $host->config()->persist();
        }
        $format = $input->getOption('format');
        switch ($format) {
            case 'json':
                $output->writeln(json_encode($config, JSON_PRETTY_PRINT));
                break;

            case 'yaml':
                $output->write(Yaml::dump($config));
                break;

            default:
                throw new \Exception("Unknown format: $format.");
        }
        return 0;
    }
}
