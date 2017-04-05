<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Deployer\Deployer;
use Deployer\Task\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
        $firstHostname = $this->deployer->hosts->getIterator()->current()->getHostname();
        $hostname = $input->getArgument('hostname') ?? $firstHostname;

        $host = $this->deployer->hosts->get($hostname);
        Context::push(new Context($host, $input, $output));

        $options = $host->sshOptions();
        $deployPath = $host->get('deploy_path', '~');

        passthru("ssh -t $options $host 'cd '''$deployPath/current'''; exec \$SHELL -l'");
    }
}
