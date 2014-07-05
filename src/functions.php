<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Deployer\Deployer;
use Deployer\Server;
use Deployer\Task;
use Deployer\Utils;

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
 * @return \Deployer\Task
 */
function task($name, $callback)
{
    return Deployer::$tasks[$name] = Task\TaskFactory::create($callback);
}

/**
 * Run command on current server.
 * @param string $command
 * @return string
 */
function run($command)
{
    $server = Server\Current::getServer();

    if (output()->isDebug()) {
        writeln("[{$server->getConfiguration()->getHost()}] $command");
    }

    return $server->run($command);
}

/**
 * Execute commands og local machine.
 * @param string $command Command to run locally.
 * @return string Output of command.
 */
function runLocally($command)
{
    return Utils\Local::run($command);
}

/**
 * Upload file or directory to current server.
 * @param string $local
 * @param string $remote
 */
function upload($local, $remote)
{
    $server = Server\Current::getServer();

    $remote = $server->getConfiguration()->getPath() . '/' . $remote;

    if (is_file($local)) {

        writeln("Upload file <info>$local</info> to <info>$remote</info>");

        $server->upload($local, $remote);

    } elseif (is_dir($local)) {

        writeln("Upload from <info>$local</info> to <info>$remote</info>");

        $finder = new Symfony\Component\Finder\Finder();
        $files = $finder
            ->files()
            ->ignoreUnreadableDirs()
            ->ignoreVCS(true)
            ->ignoreDotFiles(false)
            ->in($local);

        if (output()->isVerbose()) {
            $progress = progressHelper($files->count());
        }

        /** @var $file \Symfony\Component\Finder\SplFileInfo */
        foreach ($files as $file) {

            $server->upload(
                $file->getRealPath(),
                Utils\Path::normalize($remote . '/' . $file->getRelativePathname())
            );

            if (output()->isVerbose()) {
                $progress->advance();
            }
        }

    } else {
        throw new \RuntimeException("Uploading path '$local' does not exist.");
    }
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
    output()->writeln($message);
}

/**
 * Writes a message to the output.
 * @param string $message
 */
function write($message)
{
    output()->write($message);
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
    if (output()->isQuiet()) {
        return $default;
    }

    $dialog = Deployer::get()->getHelperSet()->get('dialog');

    $message = "<question>$message [$default]</question> ";

    return $dialog->ask(output(), $message, $default);
}

/**
 * @param string $message
 * @param bool $default
 * @return bool
 */
function askConfirmation($message, $default = false)
{
    if (output()->isQuiet()) {
        return $default;
    }

    $dialog = Deployer::get()->getHelperSet()->get('dialog');

    $message = "<question>$message [y/n]</question> ";

    if (!$dialog->askConfirmation(output(), $message, $default)) {
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
    $dialog = Deployer::get()->getHelperSet()->get('dialog');

    $message = "<question>$message</question> ";

    return $dialog->askHiddenResponse(output(), $message);
}

/**
 * @param int $count
 * @return \Symfony\Component\Console\Helper\ProgressHelper
 */
function progressHelper($count)
{
    $progress = Deployer::get()->getHelperSet()->get('progress');
    $progress->start(output(), $count);
    return $progress;
}

/**
 * @return \Symfony\Component\Console\Output\Output
 */
function output()
{
    return Deployer::get()->getOutput();
}