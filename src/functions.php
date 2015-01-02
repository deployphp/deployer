<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Deployer\Deployer;
use Deployer\Server\Local;
use Deployer\Server\Remote;
use Deployer\Server\Builder;
use Deployer\Server\Configuration;
use Deployer\Server\Environment;
use Deployer\Task\Task as TheTask;
use Deployer\Task\Context;
use Deployer\Task\GroupTask;
use Deployer\Task\Scenario\GroupScenario;
use Deployer\Task\Scenario\Scenario;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
 * @return Builder
 */
function localServer($name)
{
    $deployer = Deployer::get();

    $env = new Environment();
    $server = new Local();
    $config = new Configuration($name, 'localhost'); // Builder requires server configuration.

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
 * @throws \RuntimeException
 */
function upload($local, $remote)
{
    $server = Context::get()->getServer();

    $remote = env()->get('deploy_path') . '/' . $remote;

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

        $progress = Deployer::get()->getHelper('progress');

        if (output()->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
            $progress->start(output(), $files->count());
        }

        /** @var $file \Symfony\Component\Finder\SplFileInfo */
        foreach ($files as $file) {

            $server->upload(
                $file->getRealPath(),
                $remote . '/' . $file->getRelativePathname()
            );

            if (output()->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
                $progress->advance();
            }
        }

    } else {
        throw new \RuntimeException("Uploading path '$local' does not exist.");
    }
}

/**
 * Download file from remote server.
 *
 * @param string $local
 * @param string $remote
 */
function download($local, $remote)
{
    $server = Context::get()->getServer();
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
    // TODO
}

/**
 * @param string $key
 * @param mixed $default Default key must always be specified.
 * @return mixed
 */
function get($key, $default)
{
    // TODO
}

/**
 * @param string $message
 * @param string $default
 * @return string
 */
function ask($message, $default = null)
{
    if (output()->getVerbosity() === OutputInterface::VERBOSITY_QUIET) {
        return $default;
    }

    $helper = Deployer::get()->getHelper('question');

    $message = "<question>$message" . ($default === null) ? "" : " [$default]" . "</question> ";

    $question = new \Symfony\Component\Console\Question\Question($message, $default);

    return $helper->ask(input(), output(), $question);
}

/**
 * @param string $message
 * @param bool $default
 * @return bool
 */
function askConfirmation($message, $default = false)
{
    if (output()->getVerbosity() === OutputInterface::VERBOSITY_QUIET) {
        return $default;
    }

    $helper = Deployer::get()->getHelper('question');

    $yesOrNo = $default ? 'Y/n' : 'y/N';
    $message = "<question>$message [$yesOrNo]</question> ";

    $question = new \Symfony\Component\Console\Question\ConfirmationQuestion($message, $default);

    return $helper->ask(input(), output(), $question);
}

/**
 * @param string $message
 * @return string
 */
function askHiddenResponse($message)
{
    if (output()->getVerbosity() === OutputInterface::VERBOSITY_QUIET) {
        return '';
    }

    $helper = Deployer::get()->getHelper('question');

    $message = "<question>$message</question> ";

    $question = new \Symfony\Component\Console\Question\Question($message);
    $question->setHidden(true);
    $question->setHiddenFallback(false);

    return $helper->ask(input(), output(), $question);
}

/**
 * @return InputInterface
 */
function input()
{
    return Context::get()->getInput();
}


/**
 * @return OutputInterface
 */
function output()
{
    return Context::get()->getOutput();
}

/**
 * Return current server env.
 *
 * @param string $name
 * @param mixed $value
 * @return Environment
 */
function env($name = null, $value = null)
{
    if (null === $name && null === $value) {
        return Context::get()->getEnvironment();
    } else {
        Environment::setDefault($name, $value);
        return null;
    }
}
