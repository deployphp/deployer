<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Exception\Exception;
use Deployer\Exception\GracefulShutdownException;
use Deployer\Exception\RunException;
use Deployer\Host\FileLoader;
use Deployer\Host\Host;
use Deployer\Host\Localhost;
use Deployer\Host\Range;
use Deployer\Support\Proxy;
use Deployer\Task\Context;
use Deployer\Task\GroupTask;
use Deployer\Task\Task as T;
use Symfony\Component\Console\Exception\MissingInputException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use function Deployer\Support\array_to_string;
use function Deployer\Support\str_contains;

/**
 * @param string ...$hostname
 * @return Host|Host[]|Proxy
 */
function host(...$hostname)
{
    $deployer = Deployer::get();
    $aliases = Range::expand($hostname);

    foreach ($aliases as $alias) {
        if ($deployer->hosts->has($alias)) {
            $host = $deployer->hosts->get($alias);
            throw new \InvalidArgumentException(
                "Host \"{$host->getTag()}\" already exists.\n" .
                "If you want to override configuration options, get host with <fg=yellow>getHost</> function.\n" .
                "\n" .
                "    <fg=yellow>getHost</>(<fg=green>'{$alias}'</>);" .
                "\n"
            );
        }
    }

    if (count($aliases) === 1) {
        $host = new Host($aliases[0]);
        $deployer->hosts->set($aliases[0], $host);
        return $host;
    } else {
        $hosts = array_map(function ($hostname) use ($deployer) {
            $host = new Host($hostname);
            $deployer->hosts->set($hostname, $host);
            return $host;
        }, $aliases);
        return new Proxy($hosts);
    }
}

/**
 * @param string ...$hostnames
 * @return Localhost|Localhost[]|Proxy
 */
function localhost(...$hostnames)
{
    $deployer = Deployer::get();
    $hostnames = Range::expand($hostnames);

    if (count($hostnames) <= 1) {
        $host = count($hostnames) === 1 ? new Localhost($hostnames[0]) : new Localhost();
        $deployer->hosts->set($host->getAlias(), $host);
        return $host;
    } else {
        $hosts = array_map(function ($hostname) use ($deployer) {
            $host = new Localhost($hostname);
            $deployer->hosts->set($host->getAlias(), $host);
            return $host;
        }, $hostnames);
        return new Proxy($hosts);
    }
}

/**
 * Get host by host alias.
 *
 * @param string $alias
 * @return Host
 */
function getHost(string $alias)
{
    return Deployer::get()->hosts->get($alias);
}

/**
 * Get current host.
 *
 * @return Host
 */
function currentHost()
{
    return Context::get()->getHost();
}


/**
 * Load list of hosts from file
 *
 * @param string $file
 * @return Proxy
 */
function inventory($file)
{
    $deployer = Deployer::get();
    $fileLoader = new FileLoader();
    $fileLoader->load($file);

    $hosts = $fileLoader->getHosts();
    foreach ($hosts as $host) {
        $deployer->hosts->set($host->getAlias(), $host);
    }

    return new Proxy($hosts);
}

/**
 * Set task description.
 *
 * @param string $title
 * @return string
 */
function desc($title = null)
{
    static $store = null;

    if ($title === null) {
        return $store;
    } else {
        return $store = $title;
    }
}

/**
 * Define a new task and save to tasks list.
 *
 * Alternatively get a defined task.
 *
 * @param string $name Name of current task.
 * @param callable|array|string|null $body Callable task, array of other tasks names or nothing to get a defined tasks
 * @return Task\Task
 */
function task($name, $body = null)
{
    $deployer = Deployer::get();

    if (empty($body)) {
        return $deployer->tasks->get($name);
    }

    if (is_callable($body)) {
        $task = new T($name, $body);
    } elseif (is_array($body)) {
        $task = new GroupTask($name, $body);
    } else {
        throw new \InvalidArgumentException('Task should be a closure or array of other tasks.');
    }

    $task->saveSourceLocation();
    $deployer->tasks->set($name, $task);

    if (!empty(desc())) {
        $task->desc(desc());
        desc(''); // Clear title.
    }

    return $task;
}

/**
 * Call that task before specified task runs.
 *
 * @param string $task The task before $that should be run.
 * @param string|callable $todo The task to be run.
 * @return T|void
 */
function before($task, $todo)
{
    if (is_callable($todo)) {
        $newTask = task("before:$task", $todo);
        before($task, "before:$task");
        return $newTask;
    }
    task($task)->addBefore($todo);
}

/**
 * Call that task after specified task runs.
 *
 * @param string $task The task after $that should be run.
 * @param string|callable $todo The task to be run.
 * @return T|void
 */
function after($task, $todo)
{
    if (is_callable($todo)) {
        $newTask = task("after:$task", $todo);
        after($task, "after:$task");
        return $newTask;
    }
    task($task)->addAfter($todo);
}

/**
 * Setup which task run on failure of first.
 *
 * @param string $task The task which need to fail so $that should be run.
 * @param string $todo The task to be run.
 * @return T|void
 */
function fail($task, $todo)
{
    if (is_callable($todo)) {
        $newTask = task("fail:$task", $todo);
        fail($task, "fail:$task");
        return $newTask;
    }
    $deployer = Deployer::get();
    $deployer->fail->set($task, $todo);
}

/**
 * Add users options.
 *
 * @param string $name The option name
 * @param string|array|null $shortcut The shortcuts, can be null, a string of shortcuts delimited by | or an array of shortcuts
 * @param int|null $mode The option mode: One of the VALUE_* constants
 * @param string $description A description text
 * @param string|string[]|int|bool|null $default The default value (must be null for self::VALUE_NONE)
 */
function option($name, $shortcut = null, $mode = null, $description = '', $default = null)
{
    Deployer::get()->inputDefinition->addOption(
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
    set('working_path', parse($path));
}

/**
 * Execute a callback within a specific directory and revert back to the initial working directory.
 *
 * @param string $path
 * @param callable $callback
 */
function within($path, $callback)
{
    $lastWorkingPath = get('working_path', '');
    try {
        set('working_path', parse($path));
        $callback();
    } finally {
        set('working_path', $lastWorkingPath);
    }
}

/**
 * Run command.
 *
 * @param string $command
 * @param array $options
 * @return string
 */
function run($command, $options = [])
{
    $run = function ($command, $options) {
        $host = Context::get()->getHost();

        $command = parse($command);
        $workingPath = get('working_path', '');

        if (!empty($workingPath)) {
            $command = "cd $workingPath && ($command)";
        }

        $env = get('env', []) + ($options['env'] ?? []);
        if (!empty($env)) {
            $env = array_to_string($env);
            $command = "export $env; $command";
        }

        if ($host instanceof Localhost) {
            $process = Deployer::get()->processRunner;
            $output = $process->run($host, $command, $options);
        } else {
            $client = Deployer::get()->sshClient;
            $output = $client->run($host, $command, $options);
        }

        return rtrim($output);
    };

    if (preg_match('/^sudo\b/', $command)) {
        try {
            return $run($command, $options);
        } catch (RunException $exception) {
            $askpass = get('sudo_askpass', '/tmp/dep_sudo_pass');
            $password = get('sudo_pass', false);
            if ($password === false) {
                writeln("<fg=green;options=bold>run</> $command");
                $password = askHiddenResponse('Password:');
            }
            $run("echo -e '#!/bin/sh\necho \"%secret%\"' > $askpass", array_merge($options, ['secret' => $password]));
            $run("chmod a+x $askpass", $options);
            $run(sprintf('export SUDO_ASKPASS=%s; %s', $askpass, preg_replace('/^sudo\b/', 'sudo -A', $command)), $options);
            $run("rm $askpass", $options);
        }
    } else {
        return $run($command, $options);
    }
}


/**
 * Execute commands on local machine
 *
 * @param string $command Command to run locally.
 * @param array $options
 * @return string Output of command.
 */
function runLocally($command, $options = [])
{
    $process = Deployer::get()->processRunner;
    $command = parse($command);

    $env = get('env', []) + ($options['env'] ?? []);
    if (!empty($env)) {
        $env = array_to_string($env);
        $command = "export $env; $command";
    }

    $output = $process->run(new Localhost(), $command, $options);

    return rtrim($output);
}

/**
 * Run test command.
 * Example:
 *
 *     test('[ -d {{release_path}} ]')
 *
 * @param string $command
 * @return bool
 */
function test($command)
{
    return run("if $command; then echo 'true'; fi") === 'true';
}

/**
 * Run test command locally.
 * Example:
 *
 *     testLocally('[ -d {{local_release_path}} ]')
 *
 * @param string $command
 * @return bool
 */
function testLocally($command)
{
    return runLocally("if $command; then echo 'true'; fi") === 'true';
}

/**
 * Iterate other hosts, allowing to call run func in callback.
 *
 * @experimental
 * @param Host|Host[] $hosts
 * @param callable $callback
 */
function on($hosts, callable $callback)
{
    $deployer = Deployer::get();

    if (!is_array($hosts) && !($hosts instanceof \Traversable)) {
        $hosts = [$hosts];
    }

    foreach ($hosts as $host) {
        if ($host instanceof Host) {
            $host->getConfig()->load();
            Context::push(new Context($host, input(), output()));
            try {
                $callback($host);
                $host->getConfig()->save();
            } catch (GracefulShutdownException $e) {
                $deployer->messenger->renderException($e, $host);
            } finally {
                Context::pop();
            }
        } else {
            throw new \InvalidArgumentException("Function on can iterate only on Host instances.");
        }
    }
}

/**
 * Run task
 *
 * @experimental
 * @param string $task
 */
function invoke($task)
{
    $hosts = [Context::get()->getHost()];
    $tasks = Deployer::get()->scriptManager->getTasks($task, $hosts);

    $executor = Deployer::get()->executor;
    $executor->run($tasks, $hosts);
}

/*
 * Upload file or directory to host.
 */
function upload(string $source, string $destination, $config = [])
{
    $rsync = Deployer::get()->rsync;
    $host = currentHost();
    $source = parse($source);
    $destination = parse($destination);

    if ($host instanceof Localhost) {
        $rsync->call($host, $source, $destination, $config);
    } else {
        $rsync->call($host, $source, "{$host->getHostname()}:$destination", $config);
    }
}

/*
 * Download file or directory from host
 */
function download(string $source, string $destination, $config = [])
{
    $rsync = Deployer::get()->rsync;
    $host = currentHost();
    $source = parse($source);
    $destination = parse($destination);

    if ($host instanceof Localhost) {
        $rsync->call($host, $source, $destination, $config);
    } else {
        $rsync->call($host, "{$host->getHostname()}:$source", $destination, $config);
    }
}

/**
 * Writes an info message.
 * @param string $message
 */
function info($message)
{
    output()->writeln("<fg=green;options=bold>info</> " . parse($message));
}

/**
 * Writes an warning message.
 * @param string $message
 */
function warning($message)
{
    writeln("<fg=yellow;options=bold>warning</> <comment>" . parse($message) . "</comment>");
}

/**
 * Writes a message to the output and adds a newline at the end.
 * @param string|array $message
 * @param int $options
 */
function writeln($message, $options = 0)
{
    $host = currentHost();
    output()->writeln("[{$host->getTag()}] " . parse($message), $options);
}

/**
 * Writes a message to the output.
 * @param string $message
 * @param int $options
 */
function write($message, $options = 0)
{
    output()->write(parse($message), $options);
}

/**
 * Parse set values.
 *
 * @param string $value
 * @return string
 */
function parse($value)
{
    return Context::get()->getConfig()->parse($value);
}

/**
 * Setup configuration option.
 *
 * @param string $name
 * @param mixed $value
 */
function set($name, $value)
{
    if (!Context::has()) {
        Deployer::get()->config->set($name, $value);
    } else {
        Context::get()->getConfig()->set($name, $value);
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
    if (!Context::has()) {
        Deployer::get()->config->add($name, $array);
    } else {
        Context::get()->getConfig()->add($name, $array);
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
    if (!Context::has()) {
        return Deployer::get()->config->get($name, $default);
    } else {
        return Context::get()->getConfig()->get($name, $default);
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
    if (!Context::has()) {
        return Deployer::get()->config->has($name);
    } else {
        return Context::get()->getConfig()->has($name);
    }
}

/**
 * @param string $message
 * @param string|null $default
 * @param string[]|null $autocomplete
 * @return string
 */
function ask($message, $default = null, $autocomplete = null)
{
    Context::required(__FUNCTION__);

    if (output()->isQuiet()) {
        return $default;
    }

    /** @var QuestionHelper $helper */
    $helper = Deployer::get()->getHelper('question');

    $tag = currentHost()->getTag();
    $message = "[$tag] <question>$message</question> " . (($default === null) ? "" : "(default: $default) ");

    $question = new Question($message, $default);
    if (!empty($autocomplete)) {
        $question->setAutocompleterValues($autocomplete);
    }

    try {
        return $helper->ask(input(), output(), $question);
    } catch (MissingInputException $exception) {
        throw new Exception("Failed to read input from stdin.\nMake sure what you are asking for input not from parallel task.", $exception->getCode(), $exception);
    }
}

/**
 * @param string $message
 * @param string[] $availableChoices
 * @param string|null $default
 * @param bool|false $multiselect
 * @return string|string[]
 */
function askChoice($message, array $availableChoices, $default = null, $multiselect = false)
{
    Context::required(__FUNCTION__);

    if (empty($availableChoices)) {
        throw new \InvalidArgumentException('Available choices should not be empty');
    }

    if ($default !== null && !array_key_exists($default, $availableChoices)) {
        throw new \InvalidArgumentException('Default choice is not available');
    }

    if (output()->isQuiet()) {
        if ($default === null) {
            $default = key($availableChoices);
        }
        return [$default => $availableChoices[$default]];
    }

    $helper = Deployer::get()->getHelper('question');

    $tag = currentHost()->getTag();
    $message = "[$tag] <question>$message</question> " . (($default === null) ? "" : "(default: $default) ");

    $question = new ChoiceQuestion($message, $availableChoices, $default);
    $question->setMultiselect($multiselect);

    return $helper->ask(input(), output(), $question);
}

/**
 * @param string $message
 * @param bool $default
 * @return bool
 */
function askConfirmation($message, $default = false)
{
    Context::required(__FUNCTION__);

    if (output()->isQuiet()) {
        return $default;
    }

    $helper = Deployer::get()->getHelper('question');

    $yesOrNo = $default ? 'Y/n' : 'y/N';
    $tag = currentHost()->getTag();
    $message = "[$tag] <question>$message</question> [$yesOrNo] ";

    $question = new ConfirmationQuestion($message, $default);

    return $helper->ask(input(), output(), $question);
}

/**
 * @param string $message
 * @return string
 */
function askHiddenResponse($message)
{
    Context::required(__FUNCTION__);

    if (output()->isQuiet()) {
        return '';
    }

    $helper = Deployer::get()->getHelper('question');

    $tag = currentHost()->getTag();
    $message = "[$tag] <question>$message</question> ";

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
 * Check if command exists
 *
 * @param string $command
 * @return bool
 */
function commandExist($command)
{
    return test("hash $command 2>/dev/null");
}

function commandSupportsOption($command, $option)
{
    $man = run("(man $command 2>&1 || $command -h 2>&1 || $command --help 2>&1) | grep -- $option || true");
    if (empty($man)) {
        return false;
    }
    return str_contains($man, $option);
}

function locateBinaryPath($name)
{
    $nameEscaped = escapeshellarg($name);

    // Try `command`, should cover all Bourne-like shells
    // Try `which`, should cover most other cases
    // Fallback to `type` command, if the rest fails
    $path = run("command -v $nameEscaped || which $nameEscaped || type -p $nameEscaped");
    if (empty($path)) {
        throw new \RuntimeException("Can't locate [$nameEscaped] - neither of [command|which|type] commands are available");
    }

    // Deal with issue when `type -p` outputs something like `type -ap` in some implementations
    return trim(str_replace("$name is", "", $path));

}
