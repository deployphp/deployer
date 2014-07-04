<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Deployer\Deployer;
use Deployer\Server;
use Deployer\Task;

/**
 * @param string $name
 * @param string $domain
 * @param int $port
 * @return Server\Configuration
 */
function server($name, $domain, $port = 22)
{
    return Server\ServerFactory::create($name, $domain, $port);
}

/**
 * Define a new task and save to tasks list.
 * @param string $name Name of current task.
 * @param callable|array $callback Callable task or array of names of other tasks.
 * @return \Deployer\TaskInterface
 */
function task($name, $callback)
{
    return Task\TaskFactory::create($name, $callback);
}

/**
 * Writes a message to the output and adds a newline at the end.
 * @param string $message
 */
function writeln($message)
{
    Deployer::get()->getOutput()->writeln($message);
}

/**
 * Writes a message to the output.
 * @param string $message
 */
function write($message)
{
    Deployer::get()->getOutput()->write($message);
}


function askConfirmation($message, $default = false)
{
    $output = Deployer::get()->getOutput();
    $dialog = Deployer::get()->getConsole()->getHelperSet()->get('dialog');

    $message = "<question>$message [y/n]</question> ";

    if (!$dialog->askConfirmation($output, $message, $default)) {
        return false;
    }

    return true;
}

function ask($message, $default)
{
    $output = Deployer::get()->getOutput();
    $dialog = Deployer::get()->getConsole()->getHelperSet()->get('dialog');

    $message = "<question>$message [$default]</question> ";

    return $dialog->ask($output, $message, $default);
}

function askHiddenResponse($message)
{
    $output = Deployer::get()->getOutput();
    $dialog = Deployer::get()->getConsole()->getHelperSet()->get('dialog');

    $message = "<question>$message</question> ";

    return $dialog->askHiddenResponse($output, $message);
}