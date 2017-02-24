<?php
/* (c) Maxim Kuznetsov <skypluseg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Deployer\Deployer;
use Deployer\Server\Environment;
use Deployer\Server\SSHPipeInterface;
use Deployer\Task\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SSHCommand extends Command
{
    /**
     * @var Deployer
     */
    private $deployer;

    /**
     * @param Deployer $deployer
     */
    public function __construct(Deployer $deployer)
    {
        parent::__construct('ssh');
        $this->setDescription('Connect to selected server via ssh and cd to current release path');
        $this->addArgument('server', InputArgument::REQUIRED, 'Run tasks only on this server or group of servers');

        $this->deployer = $deployer;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $serverName = $input->getArgument('server');

        if (!$server = $this->deployer->servers->has($serverName)) {
            throw new \RuntimeException(sprintf('Unknown server `%s`. Don\'t you specify `stage` instead of `server`?', $serverName));
        }

        $server = $this->deployer->servers->get($serverName);
        $environment = $this->deployer->environments->has($serverName) ? $this->deployer->environments->get($serverName) : new Environment();
        $context = new Context($server, $environment, $input, $output);
        Context::push($context);

        if (!$server instanceof SSHPipeInterface) {
            throw new \RuntimeException('The ssh type `' . $environment->get('ssh_type') . '` doesn\'t support `ssh` task.');
        }

        $initialCommand = null;
        if ($environment->get('current_path')) {
            $initialCommand = 'cd ' . escapeshellarg($environment->get('current_path')) . ' && exec $SHELL';
        }

        $server->createSshPipe($initialCommand);
    }
}
