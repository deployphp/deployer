<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Deployer\Deployer;
use Deployer\Host\Host;
use Deployer\Host\Localhost;
use Deployer\Task\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * @codeCoverageIgnore
 */
class SshCommand extends Command
{
    /**
     * @var Deployer
     */
    private $deployer;

    /**
     * SshCommand constructor.
     * @param Deployer $deployer
     */
    public function __construct(Deployer $deployer)
    {
        parent::__construct('ssh');
        $this->setDescription('Connect to host through ssh');
        $this->deployer = $deployer;
    }

    /**
     * Configures the command
     */
    protected function configure()
    {
        $this->addArgument(
            'hostname',
            InputArgument::OPTIONAL,
            'Hostname'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $hostname = $input->getArgument('hostname');
        if (!empty($hostname)) {
            $host = $this->deployer->hosts->get($hostname);
        } else {
            $hostsAliases = [];
            foreach ($this->deployer->hosts as $host) {
                if ($host instanceof Localhost) {
                    continue;
                }
                $hostsAliases[] = $host->getAlias();
            }

            if (count($hostsAliases) === 0) {
                $output->writeln('No remote hosts.');
                return 2; // Because there are no hosts.
            }

            if (count($hostsAliases) === 1) {
                $host = $this->deployer->hosts->all()[0];
            } else {
                $helper = $this->getHelper('question');
                $question = new ChoiceQuestion(
                    '<question>Select host:</question>',
                    $hostsAliases
                );
                $question->setErrorMessage('There is no "%s" host.');

                $hostname = $helper->ask($input, $output, $question);
                $host = $this->deployer->hosts->get($hostname);
            }
        }

        $shell_path = 'exec $SHELL -l';
        if ($host->has('shell_path')) {
            $shell_path = 'exec ' . $host->get('shell_path') . ' -l';
        }

        Context::push(new Context($host, $input, $output));
        $options = $host->getSshArguments();
        $deployPath = $host->get('deploy_path', '~');

        passthru("ssh -t $options {$host->getHostname()} 'cd '''$deployPath/current'''; $shell_path'");
        return 0;
    }
}
