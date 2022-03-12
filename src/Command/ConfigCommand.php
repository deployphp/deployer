<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Command;

use Deployer\Deployer;
use Deployer\Exception\WillAskUser;
use Deployer\Task\Context;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Symfony\Component\Yaml\Yaml;

class ConfigCommand extends SelectCommand
{
    public function __construct(Deployer $deployer)
    {
        parent::__construct('config', $deployer);
        $this->setDescription('Get all configuration options for hosts');
    }

    protected function configure()
    {
        parent::configure();
        $this->addOption('format', null, InputOption::VALUE_OPTIONAL, 'The output format (json, yaml)', 'yaml');
        $this->getDefinition()->getArgument('selector')->setDefault(['all']);
    }

    protected function execute(Input $input, Output $output): int
    {
        $this->deployer->input = $input;
        $this->deployer->output = new NullOutput();
        $hosts = $this->selectHosts($input, $output);
        $data = [];
        $keys = $this->deployer->config->keys();
        define('DEPLOYER_NO_ASK', true);
        foreach ($hosts as $host) {
            Context::push(new Context($host));
            $values = [];
            foreach ($keys as $key) {
                try {
                    $values[$key] = $host->get($key);
                } catch (WillAskUser $exception) {
                    $values[$key] = ['ask' => $exception->getMessage()];
                } catch (\Throwable $exception) {
                    $values[$key] = ['error' => $exception->getMessage()];
                }
            }
            foreach ($host->config()->persist() as $k => $v) {
                $values[$k] = $v;
            }
            ksort($values);
            $data[$host->getAlias()] = $values;
            Context::pop();
        }
        $format = $input->getOption('format');
        switch ($format) {
            case 'json':
                $output->writeln(json_encode($data, JSON_PRETTY_PRINT));
                break;

            case 'yaml':
                $output->write(Yaml::dump($data));
                break;

            default:
                throw new \Exception("Unknown format: $format.");
        }
        return 0;
    }
}
