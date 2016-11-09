<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Deployer;

use Deployer\Builder\BuilderInterface;

use Deployer\ShellCommand;
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
use Deployer\Type\Result;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Deployer\Cluster\ClusterFactory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

// There are two types of functions: Deployer dependent and Context dependent.
// Deployer dependent function uses in definition stage of recipe and may require Deployer::get() method.
// Context dependent function uses while task execution and must require only Context::get() method.
// But there is also a third type of functions: mixed. Mixed function uses in definition stage and in task
// execution stage. They are acts like two different function, but have same name. Example of such function
// is set() func. This function determine in which stage it was called by Context::get() method.

/**
 * @param string $name
 * @param string|null $host
 * @param int $port
 * @return BuilderInterface
 */
function server($name, $host = null, $port = 22)
{
    $deployer = Deployer::get();

    $env = new Environment();
    $config = new Configuration($name, $host, $port);

    if (get('ssh_type') === 'ext-ssh2') {
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
 * @return BuilderInterface
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
 * @param string $name Name of the cluster
 * @param array $nodes An array of nodes' host/ip
 * @param int $port Ssh port of the nodes
 *
 * Example:
 * You should pass a cluster name and nodes array.
 * Nodes array should be as following:
 * [ '192.168.1.1', 'example.com', '192.168.1.5' ]
 * @return BuilderInterface
 */
function cluster($name, $nodes, $port = 22)
{
    $deployer = Deployer::get();

    $cluster = ClusterFactory::create($deployer, $name, $nodes, $port);

    return $cluster->getBuilder();
}


/**
 * Load server list file.
 * @param string $file
 */
function serverList($file)
{
    $bootstrap = new Bootstrap\BootstrapByConfigFile();
    $bootstrap->setConfig($file);
    $bootstrap->parseConfig();
    $bootstrap->initServers();
    $bootstrap->initClusters();
}

/**
 * Define a new task and save to tasks list.
 *
 * @param string $name Name of current task.
 * @param callable|array $body Callable task or array of other tasks names.
 * @return TheTask
 * @throws \InvalidArgumentException
 */
function task($name, $body)
{
    $deployer = Deployer::get();

    if ($body instanceof \Closure) {
        $task = new TheTask($name, $body);
        $scenario = new Scenario($name);
    } elseif (is_array($body)) {
        $task = new GroupTask();
        $scenario = new GroupScenario(array_map(function ($name) use ($deployer) {
            return $deployer->scenarios->get($name);
        }, $body));
    } else {
        throw new \InvalidArgumentException('Task should be an closure or array of other tasks.');
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
 * Add users arguments.
 *
 * Note what Deployer already has one argument: "stage".
 *
 * @param string $name
 * @param int $mode
 * @param string $description
 * @param mixed $default
 */
function argument($name, $mode = null, $description = '', $default = null)
{
    Deployer::get()->getConsole()->getUserDefinition()->addArgument(
        new InputArgument($name, $mode, $description, $default)
    );
}

/**
 * Add users options.
 *
 * @param string $name
 * @param string $shortcut
 * @param int $mode
 * @param string $description
 * @param mixed $default
 */
function option($name, $shortcut = null, $mode = null, $description = '', $default = null)
{
    Deployer::get()->getConsole()->getUserDefinition()->addOption(
        new InputOption($name, $shortcut, $mode, $description, $default)
    );
}

/**
 * Change the current working directory.
 *
 * @param string $path
 */
function cd($path)
{
    set('working_path', Context::get()->getEnvironment()->parse($path));
}

/**
 * Execute a callback within a specific directory and revert back to the initial working directory.
 *
 * @param string $path
 * @param callable $callback
 */
function within($path, $callback)
{
    $lastWorkingPath = workingPath();
    set('working_path', $path);
    $callback();
    set('working_path', $lastWorkingPath);
}

/**
 * Return the current working path.
 *
 * @return string
 */
function workingPath()
{
    return get('working_path', get(Environment::DEPLOY_PATH, ''));
}

/**
 * Run command on server.
 *
 * @param string $command
 * @return Result
 */
function run($command)
{
    $server = Context::get()->getServer();

    $command = new ShellCommand(
        Context::get()->getEnvironment()->parse($command)
    );

    $workingPath = workingPath();

    if (!empty($workingPath)) {
        $command = "cd $workingPath && $command";
    }

    if (isVeryVerbose()) {
        writeln("<fg=red>></fg=red> {$command->getForPrinting()}");
    }

    $output = $server->run($command);

    if (isDebug() && !empty($output)) {
        output()->writeln(array_map(function ($line) {
            return output()->isDecorated() ? "\033[1;30m< $line\033[0m" : "< $line";
        }, explode("\n", $output)), OutputInterface::OUTPUT_RAW);
    }

    return new Result($output);
}

/**
 * Execute commands on local machine.
 * @param string $command Command to run locally.
 * @param int $timeout (optional) Override process command timeout in seconds.
 * @return Result Output of command.
 * @throws \RuntimeException
 */
function runLocally($command, $timeout = 60)
{
    $command = new ShellCommand(
        Context::get()->getEnvironment()->parse($command)
    );

    if (isVeryVerbose()) {
        writeln("<comment>Run locally</comment>: {$command->getForPrinting()}");
    }

    $process = new Process($command);
    $process->setTimeout($timeout);
    $process->run(function ($type, $buffer) {
        if (isDebug()) {
            if ('err' === $type) {
                write("<fg=red>></fg=red> $buffer");
            } else {
                write("<fg=green>></fg=green> $buffer");
            }
        }
    });

    if (!$process->isSuccessful()) {
        throw new \RuntimeException($process->getErrorOutput());
    }

    return new Result($process->getOutput());
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
    $local = Context::get()->getEnvironment()->parse($local);
    $remote = Context::get()->getEnvironment()->parse($remote);

    if (is_file($local)) {
        writeln("Upload file <info>$local</info> to <info>$remote</info>");

        $server->upload($local, $remote);
    } elseif (is_dir($local)) {
        writeln("Upload from <info>$local</info> to <info>$remote</info>");

        $finder = new Finder();
        $files = $finder
            ->files()
            ->ignoreUnreadableDirs()
            ->ignoreVCS(true)
            ->ignoreDotFiles(false)
            ->in($local);

        /** @var $file \Symfony\Component\Finder\SplFileInfo */
        foreach ($files as $file) {
            if (isDebug()) {
                writeln("Uploading <info>{$file->getRealPath()}</info>");
            }

            $server->upload(
                $file->getRealPath(),
                $remote . '/' . $file->getRelativePathname()
            );
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
    $local = Context::get()->getEnvironment()->parse($local);
    $remote = Context::get()->getEnvironment()->parse($remote);

    $server->download($local, $remote);
}

/**
 * Writes a message to the output and adds a newline at the end.
 * @param string|array $message
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
 * Setup configuration option.
 *
 * @param string $name
 * @param mixed $value
 */
function set($name, $value)
{
    if (Context::get() === false) {
        Deployer::setDefault($name, $value);
    } else {
        Context::get()->getEnvironment()->set($name, $value);
    }
}

/**
 * Merge new config params to existing config array.
 *
 * @param string $name
 * @param array $array
 */
function add($name, $array)
{
    if (Context::get() === false) {
        Deployer::addDefault($name, $array);
    } else {
        Context::get()->getEnvironment()->add($name, $array);
    }
}

/**
 * Get configuration value.
 *
 * @param string $name
 * @param mixed|null $default
 * @return mixed
 */
function get($name, $default = null)
{
    if (Context::get() === false) {
        return Deployer::getDefault($name, $default);
    } else {
        return Context::get()->getEnvironment()->get($name, $default);
    }
}

/**
 * Check if there is such configuration option.
 *
 * @param string $name
 * @return boolean
 */
function has($name)
{
    if (Context::get() === false) {
        return Deployer::hasDefault($name);
    } else {
        return Context::get()->getEnvironment()->has($name);
    }
}

/**
 * @param string $message
 * @param string|null $default
 * @return string
 * @codeCoverageIgnore
 */
function ask($message, $default = null)
{
    if (isQuiet()) {
        return $default;
    }

    $helper = Deployer::get()->getHelper('question');

    $message = "<question>$message" . (($default === null) ? "" : " [$default]") . "</question> ";

    $question = new Question($message, $default);

    return $helper->ask(input(), output(), $question);
}

/**
 * @param string $message
 * @param bool $default
 * @return bool
 * @codeCoverageIgnore
 */
function askConfirmation($message, $default = false)
{
    if (isQuiet()) {
        return $default;
    }

    $helper = Deployer::get()->getHelper('question');

    $yesOrNo = $default ? 'Y/n' : 'y/N';
    $message = "<question>$message [$yesOrNo]</question> ";

    $question = new ConfirmationQuestion($message, $default);

    return $helper->ask(input(), output(), $question);
}

/**
 * @param string $message
 * @return string
 * @codeCoverageIgnore
 */
function askHiddenResponse($message)
{
    if (isQuiet()) {
        return '';
    }

    $helper = Deployer::get()->getHelper('question');

    $message = "<question>$message</question> ";

    $question = new Question($message);
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
 * @return bool
 */
function isQuiet()
{
    return OutputInterface::VERBOSITY_QUIET === output()->getVerbosity();
}


/**
 * @return bool
 */
function isVerbose()
{
    return OutputInterface::VERBOSITY_VERBOSE <= output()->getVerbosity();
}


/**
 * @return bool
 */
function isVeryVerbose()
{
    return OutputInterface::VERBOSITY_VERY_VERBOSE <= output()->getVerbosity();
}


/**
 * @return bool
 */
function isDebug()
{
    return OutputInterface::VERBOSITY_DEBUG <= output()->getVerbosity();
}

/**
 * Deprecated, use set()/get().
 * @deprecated
 */
function env()
{
    throw new \RuntimeException('env() function deprecated. Please, use set() or get() instead of.');
}

/**
 * Check if command exist in bash.
 *
 * @param string $command
 * @return bool
 */
function commandExist($command)
{
    return run("if hash $command 2>/dev/null; then echo 'true'; fi")->toBool();
}


/**
 * @return \Symfony\Component\EventDispatcher\EventDispatcher
 */
function dispatcher()
{
    return Deployer::get()->getDispatcher();
}
