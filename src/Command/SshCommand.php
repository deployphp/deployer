<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Command;

use Deployer\Component\Ssh\Client;
use Deployer\Deployer;
use Deployer\Host\Localhost;
use Deployer\Task\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * @codeCoverageIgnore
 */
class SshCommand extends Command
{
    use CommandCommon;

    /**
     * @var Deployer
     */
    private $deployer;

    public function __construct(Deployer $deployer)
    {
        parent::__construct('ssh');
        $this->setDescription('Connect to host through ssh');
        $this->deployer = $deployer;
    }

    protected function configure()
    {
        $this->addArgument(
            'hostname',
            InputArgument::OPTIONAL,
            'Hostname'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->telemetry();
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
                $host = $this->deployer->hosts->get($hostsAliases[0]);
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

        Context::push(new Context($host));
        $host->setSshMultiplexing(false);
        $options = $host->connectionOptionsString();
        $deployPath = $host->get('deploy_path', '~');

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            passthru("ssh -t $options {$host->connectionString()} \"cd $deployPath/current 2>/dev/null || cd $deployPath; $shell_path\"");
        } else {
            passthru("ssh -t $options {$host->connectionString()} 'cd $deployPath/current 2>/dev/null || cd $deployPath; $shell_path'");
        }
        return 0;
    }

    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        parent::complete($input, $suggestions);
        if ($input->mustSuggestArgumentValuesFor('hostname')) {
            $suggestions->suggestValues(array_keys($this->deployer->hosts->all()));
        }
    }
}
