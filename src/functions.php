<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Deployer\Deployer;
use Deployer\Environment;
use Deployer\Server;
use Deployer\Stage;
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
 * @param string $defaultStage
 */
function multistage($defaultStage = 'develop')
{
    Deployer::get()->setMultistage(true);
    Deployer::get()->setDefaultStage($defaultStage);
}

/**
 * Define a new stage
 * @param string $name Name of current stage
 * @param array $servers List of servers
 * @param array $options List of addition options
 * @param bool $default Set as default stage
 * @return Stage\Stage
 */
function stage($name, array $servers, array $options = array(), $default = false)
{
    return Stage\StageFactory::create($name, $servers, $options, $default);
}

/**
 * Define a new task and save to tasks list.
 * @param string $name Name of current task.
 * @param callable|array $body Callable task or array of names of other tasks.
 * @return \Deployer\Task
 */
function task($name, $body)
{
    return Deployer::get()->addTask($name, Task\TaskFactory::create($body, $name));
}

/**
 * Add $task to call before $name task runs.
 * @param string $name Name of task before which to call $task
 * @param callable|string|array $task
 */
function before($name, $task)
{
    $before = Deployer::get()->getTask($name);

    if ($before instanceof Task\AbstractTask) {
        $before->before(Task\TaskFactory::create($task));
    }
}

/**
 * Add $task to call after $name task runs.
 * @param string $name Name of task after which to call $task
 * @param callable|string|array $task
 */
function after($name, $task)
{
    $after = Deployer::get()->getTask($name);

    if ($after instanceof Task\AbstractTask) {
        $after->after(Task\TaskFactory::create($task));
    }
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
 * @return \Deployer\Environment
 */
function env()
{
    return Environment::getCurrent();
}

/**
 * Return current server configuration.
 * @return Server\Configuration
 */
function config()
{
    return env()->getConfig();
}

/**
 * Return path to php executable
 */
function php()
{
    return config()->getPhpPath();
}