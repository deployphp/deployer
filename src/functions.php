<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Exception\GracefulShutdownException;
use Deployer\Exception\RunException;
use Deployer\Exception\TimeoutException;
use Deployer\Host\Host;
use Deployer\Host\Localhost;
use Deployer\Host\Range;
use Deployer\Importer\Importer;
use Deployer\Support\ObjectProxy;
use Deployer\Task\Context;
use Deployer\Task\GroupTask;
use Deployer\Task\Task;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use function Deployer\Support\array_merge_alternate;
use function Deployer\Support\env_stringify;
use function Deployer\Support\is_closure;
use function Deployer\Support\str_contains;

/**
 * @return Host|Host[]|ObjectProxy
 */
function host(string ...$hostname)
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
        $hosts = array_map(function ($hostname) use ($deployer): Host {
            $host = new Host($hostname);
            $deployer->hosts->set($hostname, $host);
            return $host;
        }, $aliases);
        return new ObjectProxy($hosts);
    }
}

/**
 * @return Localhost|Localhost[]|ObjectProxy
 */
function localhost(string ...$hostnames)
{
    $deployer = Deployer::get();
    $hostnames = Range::expand($hostnames);

    if (count($hostnames) <= 1) {
        $host = count($hostnames) === 1 ? new Localhost($hostnames[0]) : new Localhost();
        $deployer->hosts->set($host->getAlias(), $host);
        return $host;
    } else {
        $hosts = array_map(function ($hostname) use ($deployer): Localhost {
            $host = new Localhost($hostname);
            $deployer->hosts->set($host->getAlias(), $host);
            return $host;
        }, $hostnames);
        return new ObjectProxy($hosts);
    }
}

/**
 * Get host by host alias.
 *
 */
function getHost(string $alias): Host
{
    return Deployer::get()->hosts->get($alias);
}

/**
 * Returns current host.
 *
 */
function currentHost(): Host
{
    return Context::get()->getHost();
}

/**
 * Returns hosts based on provided selector.
 *
 * ```php
 * on(select('stage=prod, role=db'), function ($host) {
 *     ...
 * });
 * ```
 *
 * @return Host[]
 */
function select(string $selector): array
{
    return Deployer::get()->selector->select($selector);
}

/**
 * Import other php or yaml recipes.
 *
 * ```php
 * import('recipe/common.php');
 * ```
 *
 * ```php
 * import(__DIR__ . '/config/hosts.yaml');
 * ```
 *
 * @throws Exception\Exception
 */
function import(string $file): void
{
    Importer::import($file);
}

/**
 * Set task description.
 */
function desc(?string $title = null): ?string
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
 */
function task(string $name, $body = null): Task
{
    $deployer = Deployer::get();

    if (empty($body)) {
        return $deployer->tasks->get($name);
    }

    if (is_callable($body)) {
        $task = new Task($name, $body);
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
 * @param string|callable $do The task to be run.
 *
 * @return Task|null
 */
function before(string $task, $do)
{
    if (is_closure($do)) {
        $newTask = task("before:$task", $do);
        before($task, "before:$task");
        return $newTask;
    }
    task($task)->addBefore($do);

    return null;
}

/**
 * Call that task after specified task runs.
 *
 * @param string $task The task after $that should be run.
 * @param string|callable $do The task to be run.
 *
 * @return Task|null
 */
function after(string $task, $do)
{
    if (is_closure($do)) {
        $newTask = task("after:$task", $do);
        after($task, "after:$task");
        return $newTask;
    }
    task($task)->addAfter($do);

    return null;
}

/**
 * Setup which task run on failure of $task.
 * When called multiple times for a task, previous fail() definitions will be overridden.
 *
 * @param string $task The task which need to fail so $that should be run.
 * @param string|callable $do The task to be run.
 *
 * @return Task|null
 */
function fail(string $task, $do)
{
    if (is_callable($do)) {
        $newTask = task("fail:$task", $do);
        fail($task, "fail:$task");
        return $newTask;
    }
    $deployer = Deployer::get();
    $deployer->fail->set($task, $do);

    return null;
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
function option(string $name, $shortcut = null, ?int $mode = null, string $description = '', $default = null): void
{
    Deployer::get()->inputDefinition->addOption(
        new InputOption($name, $shortcut, $mode, $description, $default)
    );
}

/**
 * Change the current working directory.
 */
function cd(string $path): void
{
    set('working_path', parse($path));
}

/**
 * Execute a callback within a specific directory and revert back to the initial working directory.
 *
 * @return mixed|null Return value of the $callback function or null if callback doesn't return anything
 */
function within(string $path, callable $callback)
{
    $lastWorkingPath = get('working_path', '');
    try {
        set('working_path', parse($path));
        return $callback();
    } finally {
        set('working_path', $lastWorkingPath);
    }

    return null;
}

/**
 * Executes given command on remote host.
 *
 * Examples:
 *
 * ```php
 * run('echo hello world');
 * run('cd {{deploy_path}} && git status');
 * run('password %secret%', secret: getenv('CI_SECRET'));
 * run('curl medv.io', timeout: 5);
 * ```
 *
 * ```php
 * $path = run('readlink {{deploy_path}}/current');
 * run("echo $path");
 * ```
 *
 * @param string $command Command to run on remote host
 * @param array|null $options Array of options will override passed named arguments.
 * @param int|null $timeout  Sets the process timeout (max. runtime). The timeout in seconds (default: 300 sec; see {{default_timeout}}, `null` to disable).
 * @param int|null $idle_timeout Sets the process idle timeout (max. time since last output) in seconds.
 * @param string|null $secret Placeholder `%secret%` can be used in command. Placeholder will be replaced with this value and will not appear in any logs.
 * @param array|null $vars Array of placeholders to replace in command: `run('echo %key%', vars: ['key' => 'anything does here']);`
 * @param array|null $env Array of environment variables: `run('echo $KEY', env: ['key' => 'value']);`
 *
 * @throws Exception\Exception|RunException|TimeoutException
 */
function run(string $command, ?array $options = [], ?int $timeout = null, ?int $idle_timeout = null, ?string $secret = null, ?array $vars = null, ?array $env = null): string
{
    $namedArguments = [];
    foreach (['timeout', 'idle_timeout', 'secret', 'vars', 'env',] as $arg) {
        if ($$arg !== null) {
            $namedArguments[$arg] = $$arg;
        }
    }
    $options = array_merge($namedArguments, $options);

    $run = function ($command, $options = []): string {
        $host = Context::get()->getHost();

        $command = parse($command);
        $workingPath = get('working_path', '');

        if (!empty($workingPath)) {
            $command = "cd $workingPath && ($command)";
        }

        $env = array_merge_alternate(get('env', []), $options['env'] ?? []);
        if (!empty($env)) {
            $env = env_stringify($env);
            $command = "export $env; $command";
        }

        $dotenv = get('dotenv', false);
        if (!empty($dotenv)) {
            $command = ". $dotenv; $command";
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
            $run("echo -e '#!/bin/sh\necho \"\$PASSWORD\"' > $askpass");
            $run("chmod a+x $askpass");
            $command = preg_replace('/^sudo\b/', 'sudo -A', $command);
            $output = $run(" SUDO_ASKPASS=$askpass PASSWORD=%sudo_pass% $command", array_merge($options, ['sudo_pass' => escapeshellarg($password)]));
            $run("rm $askpass");
            return $output;
        }
    } else {
        return $run($command, $options);
    }
}


/**
 * Execute commands on a local machine.
 *
 * Examples:
 *
 * ```php
 * $user = runLocally('git config user.name');
 * runLocally("echo $user");
 * ```
 *
 * @param string $command Command to run on localhost.
 * @param array|null $options Array of options will override passed named arguments.
 * @param int|null $timeout  Sets the process timeout (max. runtime). The timeout in seconds (default: 300 sec, `null` to disable).
 * @param int|null $idle_timeout Sets the process idle timeout (max. time since last output) in seconds.
 * @param string|null $secret Placeholder `%secret%` can be used in command. Placeholder will be replaced with this value and will not appear in any logs.
 * @param array|null $vars Array of placeholders to replace in command: `runLocally('echo %key%', vars: ['key' => 'anything does here']);`
 * @param array|null $env Array of environment variables: `runLocally('echo $KEY', env: ['key' => 'value']);`
 *
 * @throws RunException
 */
function runLocally(string $command, ?array $options = [], ?int $timeout = null, ?int $idle_timeout = null, ?string $secret = null, ?array $vars = null, ?array $env = null): string
{
    $namedArguments = [];
    foreach (['timeout', 'idle_timeout', 'secret', 'vars', 'env'] as $arg) {
        if ($$arg !== null) {
            $namedArguments[$arg] = $$arg;
        }
    }
    $options = array_merge($namedArguments, $options);

    $process = Deployer::get()->processRunner;
    $command = parse($command);

    $env = array_merge_alternate(get('env', []), $options['env'] ?? []);
    if (!empty($env)) {
        $env = env_stringify($env);
        $command = "export $env; $command";
    }

    $output = $process->run(new Localhost(), $command, $options);

    return rtrim($output);
}

/**
 * Run test command.
 * Example:
 *
 * ```php
 * if (test('[ -d {{release_path}} ]')) {
 * ...
 * }
 * ```
 *
 * @throws RunException
 */
function test(string $command): bool
{
    return run("if $command; then echo 'true'; fi") === 'true';
}

/**
 * Run test command locally.
 * Example:
 *
 *     testLocally('[ -d {{local_release_path}} ]')
 *
 * @throws RunException
 */
function testLocally(string $command): bool
{
    return runLocally("if $command; then echo 'true'; fi") === 'true';
}

/**
 * Iterate other hosts, allowing to call run a func in callback.
 *
 * ```php
 * on(select('stage=prod, role=db'), function ($host) {
 *     ...
 * });
 * ```
 *
 * ```php
 * on(getHost('prod'), function ($host) {
 *     ...
 * });
 * ```
 *
 * ```php
 * on(Deployer::get()->hosts, function ($host) {
 *     ...
 * });
 * ```
 *
 * @param Host|Host[] $hosts
 */
function on($hosts, callable $callback): void
{
    if (!is_array($hosts) && !($hosts instanceof \Traversable)) {
        $hosts = [$hosts];
    }

    foreach ($hosts as $host) {
        if ($host instanceof Host) {
            $host->config()->load();
            Context::push(new Context($host, input(), output()));
            try {
                $callback($host);
                $host->config()->save();
            } catch (GracefulShutdownException $e) {
                Deployer::get()->messenger->renderException($e, $host);
            } finally {
                Context::pop();
            }
        } else {
            throw new \InvalidArgumentException("Function on can iterate only on Host instances.");
        }
    }
}

/**
 * Runs a task.
 * ```php
 * invoke('deploy:symlink');
 * ```
 *
 * @throws Exception\Exception
 */
function invoke(string $taskName): void
{
    $task = Deployer::get()->tasks->get($taskName);
    Deployer::get()->messenger->startTask($task);
    $task->run(Context::get());
    Deployer::get()->messenger->endTask($task);
}

/**
 * Upload file or directory to host.
 *
 * > You may have noticed that there is a trailing slash (/) at the end of the first argument in the above command, this is necessary to mean “the contents of build“.
 * >
 * > The alternative, without the trailing slash, would place build, including the directory, within public. This would create a hierarchy that looks like: {{release_path}}/public/build
 *
 * @param array $config
 *
 * @throws RunException
 */
function upload(string $source, string $destination, array $config = []): void
{
    $rsync = Deployer::get()->rsync;
    $host = currentHost();
    $source = parse($source);
    $destination = parse($destination);

    if ($host instanceof Localhost) {
        $rsync->call($host, $source, $destination, $config);
    } else {
        $rsync->call($host, $source, "{$host->getConnectionString()}:$destination", $config);
    }
}

/**
 * Download file or directory from host
 *
 * @param array $config
 *
 * @throws RunException
 */
function download(string $source, string $destination, array $config = []): void
{
    $rsync = Deployer::get()->rsync;
    $host = currentHost();
    $source = parse($source);
    $destination = parse($destination);

    if ($host instanceof Localhost) {
        $rsync->call($host, $source, $destination, $config);
    } else {
        $rsync->call($host, "{$host->getConnectionString()}:$source", $destination, $config);
    }
}

/**
 * Writes an info message.
 */
function info(string $message): void
{
    writeln("<fg=green;options=bold>info</> " . parse($message));
}

/**
 * Writes an warning message.
 */
function warning(string $message): void
{
    $message = "<fg=yellow;options=bold>warning</> <comment>$message</comment>";

    if (Context::has()) {
        writeln($message);
    } else {
        Deployer::get()->output->writeln($message);
    }
}

/**
 * Writes a message to the output and adds a newline at the end.
 * @param string|array $message
 */
function writeln($message, int $options = 0): void
{
    $host = currentHost();
    output()->writeln("[{$host->getTag()}] " . parse($message), $options);
}

/**
 * Parse set values.
 */
function parse(string $value): string
{
    return Context::get()->getConfig()->parse($value);
}

/**
 * Setup configuration option.
 *
 * @param mixed $value
 */
function set(string $name, $value): void
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
 * @param array $array
 */
function add(string $name, array $array): void
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
 * @param mixed|null $default
 *
 * @return mixed
 */
function get(string $name, $default = null)
{
    if (!Context::has()) {
        return Deployer::get()->config->get($name, $default);
    } else {
        return Context::get()->getConfig()->get($name, $default);
    }
}

/**
 * Check if there is such configuration option.
 */
function has(string $name): bool
{
    if (!Context::has()) {
        return Deployer::get()->config->has($name);
    } else {
        return Context::get()->getConfig()->has($name);
    }
}

/**
 * @param string[]|null $autocomplete
 */
function ask(string $message, ?string $default = null, ?array $autocomplete = null): ?string
{
    Context::required(__FUNCTION__);

    if (output()->isQuiet()) {
        return $default;
    }

    if (Deployer::isWorker()) {
        return Deployer::proxyCallToMaster(currentHost(), __FUNCTION__, ...func_get_args());
    }

    /** @var QuestionHelper */
    $helper = Deployer::get()->getHelper('question');

    $tag = currentHost()->getTag();
    $message = "[$tag] <question>$message</question> " . (($default === null) ? "" : "(default: $default) ");

    $question = new Question($message, $default);
    if (!empty($autocomplete)) {
        $question->setAutocompleterValues($autocomplete);
    }

    return $helper->ask(input(), output(), $question);
}

/**
 * @param string[] $availableChoices
 * @param bool|false $multiselect
 *
 * @return string|string[]
 */
function askChoice(string $message, array $availableChoices, ?string $default = null, bool $multiselect = false)
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

    if (Deployer::isWorker()) {
        return Deployer::proxyCallToMaster(currentHost(), __FUNCTION__, ...func_get_args());
    }

    /** @var QuestionHelper */
    $helper = Deployer::get()->getHelper('question');

    $tag = currentHost()->getTag();
    $message = "[$tag] <question>$message</question> " . (($default === null) ? "" : "(default: $default) ");

    $question = new ChoiceQuestion($message, $availableChoices, $default);
    $question->setMultiselect($multiselect);

    return $helper->ask(input(), output(), $question);
}

function askConfirmation(string $message, bool $default = false): bool
{
    Context::required(__FUNCTION__);

    if (output()->isQuiet()) {
        return $default;
    }

    if (Deployer::isWorker()) {
        return Deployer::proxyCallToMaster(currentHost(), __FUNCTION__, ...func_get_args());
    }

    /** @var QuestionHelper */
    $helper = Deployer::get()->getHelper('question');

    $yesOrNo = $default ? 'Y/n' : 'y/N';
    $tag = currentHost()->getTag();
    $message = "[$tag] <question>$message</question> [$yesOrNo] ";

    $question = new ConfirmationQuestion($message, $default);

    return $helper->ask(input(), output(), $question);
}

function askHiddenResponse(string $message): string
{
    Context::required(__FUNCTION__);

    if (output()->isQuiet()) {
        return '';
    }

    if (Deployer::isWorker()) {
        return (string)Deployer::proxyCallToMaster(currentHost(), __FUNCTION__, ...func_get_args());
    }

    /** @var QuestionHelper */
    $helper = Deployer::get()->getHelper('question');

    $tag = currentHost()->getTag();
    $message = "[$tag] <question>$message</question> ";

    $question = new Question($message);
    $question->setHidden(true);
    $question->setHiddenFallback(false);

    return (string)$helper->ask(input(), output(), $question);
}

function input(): InputInterface
{
    return Context::get()->getInput();
}

function output(): OutputInterface
{
    return Context::get()->getOutput();
}

/**
 * Check if command exists
 *
 * @throws RunException
 */
function commandExist(string $command): bool
{
    return test("hash $command 2>/dev/null");
}

/**
 * @throws RunException
 */
function commandSupportsOption(string $command, string $option): bool
{
    $man = run("(man $command 2>&1 || $command -h 2>&1 || $command --help 2>&1) | grep -- $option || true");
    if (empty($man)) {
        return false;
    }
    return str_contains($man, $option);
}

/**
 * @throws RunException
 */
function locateBinaryPath(string $name): string
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
