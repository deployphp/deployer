<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Deployer\Tool\Context;

/**
 * Create new task.
 * @param string $name
 * @param string|callable|array $descriptionOrCallback
 * @param null|callable|array $callback
 */
function task($name, $descriptionOrCallback, $callback = null)
{
    Context::get()->task($name, $descriptionOrCallback, $callback);
}

/**
 * Start Console application.
 */
function start()
{
    Context::get()->start();
}

/**
 * @param string $message
 */
function write($message)
{
    Context::get()->writeln($message);
}

/**
 * @param string $message
 */
function writeln($message)
{
    Context::get()->writeln($message);
}

/**
 * @param $message
 *
 * @return bool
 */
function askConfirmation($message)
{
    return Context::get()->askConfirmation($message);
}

/**
 * @param string $message
 * @param string $default
 *
 * @return string
 */
function ask($message, $default = null)
{
    return Context::get()->ask($message, $default);
}

/**
 * @param $message
 *
 * @return string
 */
function askHiddenResponse($message)
{
    return Context::get()->askHiddenResponse($message);
}

/**
 * @param string $server
 * @param string $user
 * @param string|Deployer\Tool\Remote\Key $password
 * @param string|null $group
 */
function connect($server, $user, $password, $group = null)
{
    Context::get()->connect($server, $user, $password, $group);
}

/**
 * @param string $path
 * @param string|null $password
 * @return \Deployer\Tool\Remote\Rsa
 */
function rsa($path, $password = null)
{
    return new \Deployer\Tool\Remote\Rsa($path, $password);
}

/**
 * @param array $ignore
 */
function ignore($ignore = array())
{
    Context::get()->ignore($ignore);
}

/**
 * @param string $from
 * @param string $to
 */
function upload($from, $to)
{
    Context::get()->upload($from, $to);
}

/**
 * 
 * @param type $from
 * @param type $to
 */
function download($from, $to)
{
    Context::get()->download($from, $to);
}


/**
 * Change current directory for whole connect session.
 * @param string $directory
 */
function cd($directory)
{
    Context::get()->cd($directory);
}

/**
 * Run command on remote server.
 * @param string $command
 */
function run($command)
{
    return Context::get()->run($command);
}

/**
 * Run command locally.
 * @param string $command
 */
function runLocally($command)
{
    return Context::get()->runLocally($command);
}

/**
 * Run task
 * @param string $name
 */
function runTask($name)
{
    Context::get()->runTask($name);
}

/**
 * @param string $group
 * @param callable $action
 */
function group($group, \Closure $action)
{
    Context::get()->group($group, $action);
}