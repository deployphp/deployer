<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Exception\RuntimeException;
use Deployer\Host\FileLoader;
use Deployer\Host\Host;
use Deployer\Host\Localhost;
use Deployer\Host\Range;
use function Deployer\Support\array_to_string;
use Deployer\Support\Proxy;
use Deployer\Task\Context;
use Deployer\Task\GroupTask;
use Deployer\Task\Task as T;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

// There are two types of functions: Deployer dependent and Context dependent.
// Deployer dependent function uses in definition stage of recipe and may require Deployer::get() method.
// Context dependent function uses while task execution and must require only Context::get() method.
// But there is also a third type of functions: mixed. Mixed function uses in definition stage and in task
// execution stage. They are acts like two different function, but have same name. Example of such function
// is set() func. This function determine in which stage it was called by Context::get() method.

/**
 * @param array ...$hostnames
 * @return Host|Host[]|Proxy
 */
function host(...$hostnames)
{
    $deployer = Deployer::get();
    $hostnames = Range::expand($hostnames);

    // Return hosts if has
    if ($deployer->hosts->has($hostnames[0])) {
        if (count($hostnames) === 1) {
            return $deployer->hosts->get($hostnames[0]);
        } else {
            return array_map([$deployer->hosts, 'get'], $hostnames);
        }
    }

    // Add otherwise
    if (count($hostnames) === 1) {
        $host = new Host($hostnames[0]);
        $deployer->hosts->set($hostnames[0], $host);
        return $host;
    } else {
        $hosts = array_map(function ($hostname) use ($deployer) {
            $host = new Host($hostname);
            $deployer->hosts->set($hostname, $host);
            return $host;
        }, $hostnames);
        return new Proxy($hosts);
    }
}

/**
 * @param array ...$hostnames
 * @return Localhost|Localhost[]|Proxy
 */
function localhost(...$hostnames)
{
    $deployer = Deployer::get();
    $hostnames = Range::expand($hostnames);

    if (count($hostnames) <= 1) {
        $host = count($hostnames) === 1 ? new Localhost($hostnames[0]) : new Localhost();
        $deployer->hosts->set($host->getHostname(), $host);
        return $host;
    } else {
        $hosts = array_map(function ($hostname) use ($deployer) {
            $host = new Localhost($hostname);
            $deployer->hosts->set($host->getHostname(), $host);
            return $host;
        }, $hostnames);
        return new Proxy($hosts);
    }
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
        $deployer->hosts->set($host->getHostname(), $host);
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
 * @throws \InvalidArgumentException
 */
function task($name, $body = null)
{
    $deployer = Deployer::get();

    if (empty($body)) {
        $task = $deployer->tasks->get($name);
        return $task;
    }

    if (is_callable($body)) {
        $task = new T($name, $body);
    } elseif (is_array($body)) {
        $task = new GroupTask($name, $body);
    } elseif (is_string($body)) {
        $task = new T($name, function () use ($body) {
            cd('{{release_path}}');
            run($body);
        });
    } else {
        throw new \InvalidArgumentException('Task should be a closure, string or array of other tasks.');
    }

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
 * @param string $it The task before $that should be run.
 * @param string $that The task to be run.
 */
function before($it, $that)
{
    $deployer = Deployer::get();
    $beforeTask = $deployer->tasks->get($it);

    $beforeTask->addBefore($that);
}

/**
 * Call that task after specified task runs.
 *
 * @param string $it The task after $that should be run.
 * @param string $that The task to be run.
 */
function after($it, $that)
{
    $deployer = Deployer::get();
    $afterTask = $deployer->tasks->get($it);

    $afterTask->addAfter($that);
}

/**
 * Setup which task run on failure of first.
 *
 * @param string $it The task which need to fail so $that should be run.
 * @param string $that The task to be run.
 */
function fail($it, $that)
{
    $deployer = Deployer::get();
    $deployer->fail->set($it, $that);
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
    try {
        set('working_path', parse($path));
    } catch (RuntimeException $e) {
        throw new \Exception('Unable to change directory into "'. $path .'"', 0, $e);
    }
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
    $host = Context::get()->getHost();
    $hostname = $host->getHostname();

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
        $output = $process->run($hostname, $command, $options);
    } else {
        $client = Deployer::get()->sshClient;
        $output = $client->run($host, $command, $options);
    }

    return rtrim($output);
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
    $hostname = 'localhost';
    $command = parse($command);

    $env = get('env', []) + ($options['env'] ?? []);
    if (!empty($env)) {
        $env = array_to_string($env);
        $command = "export $env; $command";
    }

    $output = $process->run($hostname, $command, $options);

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
    $input = Context::has() ? input() : null;
    $output = Context::has() ? output() : null;

    if (!is_array($hosts) && !($hosts instanceof \Traversable)) {
        $hosts = [$hosts];
    }

    foreach ($hosts as $host) {
        if ($host instanceof Host) {
            Context::push(new Context($host, $input, $output));
            try {
                $callback($host);
            } finally {
                Context::pop();
            }
        } else {
            throw new \InvalidArgumentException("Function on can iterate only on Host instances.");
        }
    }
}

/**
 * Return hosts based on roles.
 *
 * @experimental
 * @param string[] $roles
 * @return Host[]
 */
function roles(...$roles)
{
    return Deployer::get()->hostSelector->getByRoles($roles);
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

    $executor = Deployer::get()->seriesExecutor;
    $executor->run($tasks, $hosts);
}

/**
 * Upload file or directory to host
 *
 * @param string $source
 * @param string $destination
 * @param array $config
 */
function upload($source, $destination, array $config = [])
{
    $rsync = Deployer::get()->rsync;
    $host = Context::get()->getHost();
    $source = parse($source);
    $destination = parse($destination);

    if ($host instanceof Localhost) {
        $rsync->call($host->getHostname(), $source, $destination, $config);
    } else {
        if (!isset($config['options']) || !is_array($config['options'])) {
            $config['options'] = [];
        }

        $sshArguments = $host->getSshArguments()->getCliArguments();
        if (empty($sshArguments) === false) {
            $config['options'][] = "-e 'ssh $sshArguments'";
        }

        if ($host->has("become")) {
            $config['options'][]  = "--rsync-path='sudo -H -u " . $host->get('become') . " rsync'";
        }

        $rsync->call($host->getHostname(), $source, "$host:$destination", $config);
    }
}

/**
 * Download file or directory from host
 *
 * @param string $destination
 * @param string $source
 * @param array $config
 */
function download($source, $destination, array $config = [])
{
    $rsync = Deployer::get()->rsync;
    $host = Context::get()->getHost();
    $source = parse($source);
    $destination = parse($destination);

    if ($host instanceof Localhost) {
        $rsync->call($host->getHostname(), $source, $destination, $config);
    } else {
        if (!isset($config['options']) || !is_array($config['options'])) {
            $config['options'] = [];
        }

        $sshArguments = $host->getSshArguments()->getCliArguments();
        if (empty($sshArguments) === false) {
            $config['options'][] = "-e 'ssh $sshArguments'";
        }

        if ($host->has("become")) {
            $config['options'][]  = "--rsync-path='sudo -H -u " . $host->get('become') . " rsync'";
        }

        $rsync->call($host->getHostname(), "$host:$source", $destination, $config);
    }
}

/**
 * Writes a message to the output and adds a newline at the end.
 * @param string|array $message
 * @param int $options
 */
function writeln($message, $options = 0)
{
    output()->writeln(parse($message), $options);
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
 * Setup configuration option.
 *
 * @param string $name
 * @param mixed $value
 */
function set($name, $value)
{
    if (!Context::has()) {
        Deployer::setDefault($name, $value);
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
        Deployer::addDefault($name, $array);
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
        return Deployer::getDefault($name, $default);
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
        return Deployer::hasDefault($name);
    } else {
        return Context::get()->getConfig()->has($name);
    }
}

/**
 * @param string $message
 * @param string|null $default
 * @param string[]|null $suggestedChoices
 * @return string
 * @codeCoverageIgnore
 */
function ask($message, $default = null, $suggestedChoices = null)
{
    Context::required(__FUNCTION__);

    if (($suggestedChoices !== null) && (empty($suggestedChoices))) {
        throw new \InvalidArgumentException('Suggested choices should not be empty');
    }

    if (isQuiet()) {
        return $default;
    }

    $helper = Deployer::get()->getHelper('question');

    $message = "<question>$message" . (($default === null) ? "" : " [$default]") . "</question> ";

    $question = new Question($message, $default);

    if (empty($suggestedChoices) === false) {
        $question->setAutocompleterValues($suggestedChoices);
    }

    return $helper->ask(input(), output(), $question);
}

/**
 * @param string $message
 * @param string[] $availableChoices
 * @param string|null $default
 * @param bool|false $multiselect
 * @return string|string[]
 * @codeCoverageIgnore
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

    if (isQuiet()) {
        if ($default === null) {
            $default = key($availableChoices);
        }
        return [$default => $availableChoices[$default]];
    }

    $helper = Deployer::get()->getHelper('question');

    $message = "<question>$message" . (($default === null) ? "" : " [$default]") . "</question> ";

    $question = new ChoiceQuestion($message, $availableChoices, $default);
    $question->setMultiselect($multiselect);

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
    Context::required(__FUNCTION__);

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
    Context::required(__FUNCTION__);

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
    return test("[[ $(man $command 2>&1 || $command -h 2>&1 || $command --help 2>&1) =~ '$option' ]]");
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

function locateBinaryPath($name)
{
    $nameEscaped = escapeshellarg($name);

    // Try `command`, should cover all Bourne-like shells
    // Try `which`, should cover most other cases
    // Fallback to `type` command, if the rest fails
    $path = run("command -v $nameEscaped || which $nameEscaped || type -p $nameEscaped");
    if ($path) {
        // Deal with issue when `type -p` outputs something like `type -ap` in some implementations
        return trim(str_replace("$name is", "", $path));
    }

    throw new \RuntimeException("Can't locate [$nameEscaped] - neither of [command|which|type] commands are available");
}
