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
 * @param string $server
 * @param string $user
 * @param string|Deployer\Tool\Remote\Key $password
 * @param string|null $name
 */
function connect($server, $user, $password, $name = null)
{
    Context::get()->connect($server, $user, $password, $name);
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
 * @param string|null $serverName
 */
function upload($from, $to, $serverName = null)
{
    Context::get()->upload($from, $to, $serverName);
}

/**
 * Change current directory for whole connect session.
 * @param string $directory
 * @param string|null $serverName
 */
function cd($directory, $serverName = null)
{
    Context::get()->cd($directory, $serverName);
}

/**
 * Run command on remote server.
 * @param string $command
 * @param string|null $serverName
 */
function run($command, $serverName = null)
{
    Context::get()->run($command, $serverName);
}

/**
 * Run command locally.
 * @param string $command
 */
function runLocally($command)
{
    Context::get()->runLocally($command);
}