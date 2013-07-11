<?php
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
 */
function connect($server, $user, $password)
{
    Context::get()->connect($server, $user, $password);
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
    Context::get()->run($command);
}