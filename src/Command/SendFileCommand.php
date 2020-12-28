<?php declare(strict_types=1);
/* (c) Herbert Maschke <thyseus@pm.me>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Command;

use Deployer\Component\Ssh\Client;
use Deployer\Deployer;
use Deployer\Host\Host;
use Deployer\Host\Localhost;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption as Option;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * @codeCoverageIgnore
 */
class SendFileCommand extends SelectCommand
{
    use CommandCommon;
    use CustomOption;

    public function __construct(Deployer $deployer)
    {
        parent::__construct('sendfile', $deployer);

        $this->setDescription('Send a file to given host(s)');

        $this->deployer = $deployer;
    }

    protected function configure()
    {
        $this->addArgument(
            'source',
            InputArgument::REQUIRED,
            'Source'
        );
        $this->addArgument(
            'hostname',
            InputArgument::OPTIONAL,
            'Hostname'
        );
        $this->addOption(
            'targetPath',
            '-t',
            InputArgument::OPTIONAL,
            'Path on the host. Defaults to deploy_path of host'
        );
        $this->addOption(
            'scpOptions',
            null,
            InputArgument::OPTIONAL,
            'Options to be passed to the scp command'
        );
    }

    protected function ensureFile(string $source): void
    {
        if (!file_exists($source)) {
            $this->deployer->output->writeln("<error>Error:</error> Source file <info>$source</info> does not exist.");
            exit(1);
        }

        if (!is_readable($source)) {
            $this->deployer->output->writeln("<error>Error:</error> Source file <info>$source</info> is not readable.");
            exit(1);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->deployer->input = $input;
        $this->deployer->output = $output;

        $hostname = $input->getArgument('hostname');
        $source = $input->getArgument('source');

        $this->ensureFile($source);

        $hosts = [];
        if (!empty($hostname)) {
            $hosts = [$this->deployer->hosts->get($hostname)];
        } else {
            foreach ($this->deployer->hosts as $host) {
                if ($host instanceof Localhost) {
                    continue;
                }
                $hosts[] = $host;
            }

            if (count($hosts) === 0) {
                $output->writeln('No remote hosts.');
                return 2;
            }
        }

        $scpOptions = $input->getOption('scpOptions');

        $verbosity = $this->determineVerbosity($output);

        foreach ($hosts as $host) {
            $port = '';

            if ($input->getOption('targetPath')) {
                $deployPath = $input->getOption('targetPath');
            } else {
                $deployPath = $host->get('deploy_path', '~');
            }

            if ($host->has('port')) {
                $port = " -P {$host->getPort()}";
            }

            $connectionString = $host->getConnectionString();

            $command = "scp$verbosity$port $source $connectionString:$deployPath";

            if ($output->getVerbosity() != OutputInterface::VERBOSITY_QUIET) {
                $output->writeln("Executing: $command");
            }

            passthru($command);
        }

        return 0;
    }

    protected function determineVerbosity(OutputInterface $output): string
    {
        if ($output->getVerbosity() == OutputInterface::VERBOSITY_DEBUG) {
            return ' -v ';
        }

        return '';
    }
}
