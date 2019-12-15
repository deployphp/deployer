<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Deployer\Deployer;
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
            $hosts = $this->deployer->hosts->select(function ($host) {
                return !($host instanceof Localhost);
            });

            if (count($hosts) === 0) {
                $output->writeln('No remote hosts.');
                return; // Because there are no hosts.
            } elseif (count($hosts) === 1) {
                $host = array_shift($hosts);
            } else {
                $helper = $this->getHelper('question');
                $question = new ChoiceQuestion(
                    'Select host:',
                    $hosts
                );
                $question->setErrorMessage('There is no "%s" host.');

                $hostname = $helper->ask($input, $output, $question);
                $host = $this->deployer->hosts->get($hostname);
            }
        }

        $shell_path = 'exec $SHELL -l';
        if ($host->has('shell_path')) {
            $shell_path = 'exec '.$host->get('shell_path').' -l';
        }

        Context::push(new Context($host, $input, $output));
        $options = $host->getSshArguments();
        $deployPath = $host->get('deploy_path', '~');

        passthru("ssh -t $options $host 'cd '''$deployPath/current'''; $shell_path'");
        return 0;
    }
}
