<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Deployer\Deployer;
use Deployer\Server\Remote;
use Deployer\Server\Builder;
use Deployer\Server\Configuration;
use Deployer\Server\Environment;
use Deployer\Task\Task as TheTask;
use Deployer\Task\GroupTask;
use Deployer\Task\Scenario\GroupScenario;
use Deployer\Task\Scenario\Scenario;

/**
 * @param string $name
 * @param string $domain
 * @param int $port
 * @return Builder
 */
function server($name, $domain, $port = 22)
{
    $deployer = Deployer::get();
    
    $env = new Environment();
    $config = new Configuration($name, $domain, $port);
    
    if (function_exists('ssh2_exec')) {
        $server = new Remote\SshExtension($config);
    } else {
        $server = new Remote\PhpSecLib($config);
    }
    
    $deployer->servers->set($name, $server);
    $deployer->environments->set($name, $env);
    
    return new Builder($config, $env);
}

/**
 * @param string $name
 * @param array $servers
 */
function serverGroup($name, $servers) 
{
    $deployer = Deployer::get();
    
    $deployer->serverGroups->set($name, $servers);
}

/**
 * Define a new task and save to tasks list.
 *
 * @param string $name Name of current task.
 * @param callable|array $body Callable task or array of other tasks names.
 * @return TheTask
 * @throws InvalidArgumentException
 */
function task($name, $body)
{
    $deployer = Deployer::get();

    if (is_callable($body)) {
        $task = new TheTask($body);
        $scenario = new Scenario($name);
    } else if (is_array($body)) {
        $task = new GroupTask();
        $scenario = new GroupScenario(array_map(function ($name) use ($deployer) {
            return $deployer->scenarios->get($name);
        }, $body));
    } else {
        throw new InvalidArgumentException('Task should be an closure or array of other tasks.');
    }

    $deployer->tasks->set($name, $task);
    $deployer->scenarios->set($name, $scenario);

    return $task;
}

/**
 * Call that task before specified task runs.
 *
 * @param string $it
 * @param string $that
 */
function before($it, $that)
{
    $deployer = Deployer::get();
    $beforeScenario = $deployer->scenarios->get($it);
    $scenario = $deployer->scenarios->get($that);

    $beforeScenario->addBefore($scenario);
}

/**
 * Call that task after specified task runs.
 *
 * @param string $it
 * @param string $that
 */
function after($it, $that)
{
    $deployer = Deployer::get();
    $afterScenario = $deployer->scenarios->get($it);
    $scenario = $deployer->scenarios->get($that);

    $afterScenario->addAfter($scenario);
}

/**
 * Set working path for task.
 * @param string $path
 */
function cd($path)
{
    env()->setWorkingPath($path);
}

/**
 * Run command on current server.
 * @param string $command
 * @param bool $raw If true $command will not be modified.
 * @return string
 */
function run($command, $raw = false)
{
    $server = env()->getServer();
    $config = config();
    $workingPath = env()->getWorkingPath();

    if (!$raw) {
        $command = "cd {$workingPath} && $command";
    }

    if (output()->isDebug()) {
        writeln("[{$server->getConfiguration()->getHost()}] $command");
    }

    $output = $server->run($command);

    if (output()->isDebug()) {
        array_map(function ($output) use ($config) {
            write("[{$config->getHost()}] :: $output\n");
        }, explode("\n", $output));
    }

    return $output;
}

/**
 * Execute commands on local machine.
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
    $server = env()->getServer();

    $remote = config()->getPath() . '/' . $remote;

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
    $server = env()->getServer();
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
 * Prints info.
 * @param string $description
 * @deprecated Use writeln("<info>...</info>") instead of.
 */
function info($description)
{
    writeln("<info>$description</info>");
}

/**
 * Prints "ok" sign.
 * @deprecated
 */
function ok()
{
    writeln("<info>✔</info>");
}

/**
 * @param string $key
 * @param mixed $value
 */
function set($key, $value)
{
    Deployer::get()->setParameter($key, $value);
}

/**
 * @param string $key
 * @param mixed $default Default key must always be specified.
 * @return mixed
 */
function get($key, $default)
{
    return Deployer::get()->getParameter($key, $default);
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

    $yesOrNo = $default ? 'Y/n' : 'y/N';
    $message = "<question>$message [$yesOrNo]</question> ";

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

/**
 * Return current server env.
 * @return \Deployer\CurrentEnvironment
 */
function env()
{
    return CurrentEnvironment::getCurrent();
}

/**
 * Return current server configuration.
 * @return Server\Configuration
 */
function config()
{
    return env()->getConfig();
}
