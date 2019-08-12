<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Deployer\Deployer;
use Deployer\Exception\GracefulShutdownException;
use Deployer\Exception\NonFatalException;
use Deployer\Host\Storage;
use Deployer\Task\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputOption as Option;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @codeCoverageIgnore
 */
class WorkerCommand extends Command
{
    private $deployer;
    private $host;

    public function __construct(Deployer $deployer)
    {
        parent::__construct('worker');
        $this->deployer = $deployer;
        $this->setHidden(true);
        $this->addOption(
            'host',
            null,
            InputOption::VALUE_REQUIRED
        );
        $this->addOption(
            'task',
            null,
            InputOption::VALUE_REQUIRED
        );
        $this->addOption(
            'config-file',
            null,
            InputOption::VALUE_REQUIRED
        );
        $this->addOption(
            'option',
            'o',
            Option::VALUE_REQUIRED | Option::VALUE_IS_ARRAY,
            'Sets configuration option'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->parseOptions($input->getOption('option'));
        $output->setDecorated($this->deployer->config['decorated']);
        $output->setVerbosity($this->deployer->config['verbosity']);

        try {
            $this->doExecute($input, $output);
            return 0;
        } catch (GracefulShutdownException $e) {
            $this->deployer->informer->taskException($e, $this->host);
            return 1;
        } catch (NonFatalException $e) {
            $this->deployer->informer->taskException($e, $this->host);
            return 2;
        } catch (\Throwable $e) {
            $this->deployer->informer->taskException($e, $this->host);
            return 255;
        }
    }

    private function doExecute(InputInterface $input, OutputInterface $output)
    {
        $task = $input->getOption('task');
        $hostname = $input->getOption('host');
        $this->host = $this->deployer->hosts->get($hostname);

        // Load host configuration from file and replace host config collection with PersistentCollection.
        Storage::setup($this->host, $input->getOption('config-file'));

        $task = $this->deployer->tasks->get($task);
        if ($task->shouldBePerformed($this->host)) {
            $task->run(new Context($this->host, $input, $output));
            $this->deployer->informer->endOnHost($hostname);
        }

        Storage::flush($this->host);
    }

    private function parseOptions(array $options)
    {
        foreach ($options as $option) {
            list($name, $value) = explode('=', $option);
            $value = $this->castValueToPhpType($value);
            $this->deployer->config->set($name, $value);
        }
    }

    private function castValueToPhpType($value)
    {
        switch ($value) {
            case 'true':
                return true;
            case 'false':
                return false;
            default:
                return $value;
        }
    }
}
