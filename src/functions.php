<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Deployer\Deployer;
use Deployer\Parameter;
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
 * Run command on current server.
 * @param string $command
 * @return string
 */
function run($command)
{
    $server = Server\Current::getServer();
    return $server->run($command);
}

/**
 * Upload file or directory to current server.
 * @param string $local
 * @param string $remote
 */
function upload($local, $remote)
{
    $server = Server\Current::getServer();
    $server->upload($local, $remote);
}

/**
 * Download ONE FILE from remote server.
 * @param string $local
 * @param string $remote
 */
function download($local, $remote)
{
    $server = Server\Current::getServer();
    $server->download($local, $remote);
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

/**
 * @param string $key
 * @param mixed $value
 */
function set($key, $value)
{
    Deployer::$parameters[$key] = $value;
}

/**
 * @param string $key
 * @param mixed $default Default key must always be specified.
 * @return mixed
 */
function get($key, $default)
{
    return array_key_exists($key, Deployer::$parameters) ? Deployer::$parameters[$key] : $default;
}

/**
 * @param string $message
 * @param string $default
 * @return string
 */
function ask($message, $default)
{
    $output = Deployer::get()->getOutput();
    $dialog = Deployer::get()->getConsole()->getHelperSet()->get('dialog');

    $message = "<question>$message [$default]</question> ";

    return $dialog->ask($output, $message, $default);
}

/**
 * @param string $message
 * @param bool $default
 * @return bool
 */
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

/**
 * @param string $message
 * @return string
 */
function askHiddenResponse($message)
{
    $output = Deployer::get()->getOutput();
    $dialog = Deployer::get()->getConsole()->getHelperSet()->get('dialog');

    $message = "<question>$message</question> ";

    return $dialog->askHiddenResponse($output, $message);
}