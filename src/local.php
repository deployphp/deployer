<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Deployer\local;

use Deployer\Builder\BuilderInterface;
use Deployer\Deployer;
use Deployer\Server\Builder;
use Deployer\Server\Configuration;
use Deployer\Server\Environment;
use Deployer\Server\Local;
use Deployer\Type\Result;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * @param string $name
 * @return BuilderInterface
 */
function server($name)
{
    $deployer = Deployer::get();

    $env = new Environment();
    $config = new Configuration($name, 'localhost'); // Builder requires server configuration.
    $server = new Local($config);

    $deployer->servers->set($name, $server);
    $deployer->environments->set($name, $env);

    return new Builder($config, $env);
}


/**
 * Execute commands on local machine.
 * @param string $command Command to run locally.
 * @param int $timeout (optional) Override process command timeout in seconds.
 * @return Result Output of command.
 * @throws \RuntimeException
 */
function run($command, $timeout = 60)
{
    $command = \Deployer\parse($command);

    if (\Deployer\isVeryVerbose()) {
        \Deployer\writeln("[localhost] <fg=red>></fg=red> : $command");
    }

    \Deployer\logger("[localhost] > $command");

    $process = new Process($command);
    $process->setTimeout($timeout);
    $process->run(function ($type, $buffer) {
        if (\Deployer\isDebug()) {
            if ('err' === $type) {
                \Deployer\write("<fg=red>></fg=red> $buffer");
            } else {
                \Deployer\write("<fg=green>></fg=green> $buffer");
            }
        }
    });

    if (!$process->isSuccessful()) {
        throw new ProcessFailedException($process);
    }

    $output = $process->getOutput();

    \Deployer\logger("[localhost] < $output");

    return new Result($output);
}
