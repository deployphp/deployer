<?php

declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Exception\Exception;
use Deployer\Exception\GracefulShutdownException;
use Deployer\Exception\RunException;
use Deployer\Exception\TimeoutException;
use Deployer\Exception\WillAskUser;
use Deployer\Host\Host;
use Deployer\Host\Localhost;
use Deployer\Host\Range;
use Deployer\Import\Import;
use Deployer\Ssh\RunParams;
use Deployer\Support\ObjectProxy;
use Deployer\Task\Context;
use Deployer\Task\GroupTask;
use Deployer\Task\Task;
use Deployer\Utility\Httpie;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

use function Deployer\Support\array_merge_alternate;
use function Deployer\Support\is_closure;

/**
 * Define one or more hosts for deployment.
 *
 * ```php
 * host('example.org');
 * host('prod.example.org', 'staging.example.org');
 * ```
 *
 * Inside a task, returns the `Host` instance for the given alias:
 * ```php
 * task('test', function () {
 *     $port = host('example.org')->get('port');
 * });
 * ```
 */
function host(string ...$hostname): Host|ObjectProxy
{
    $deployer = Deployer::get();
    if (count($hostname) === 1 && $deployer->hosts->has($hostname[0])) {
        return $deployer->hosts->get($hostname[0]);
    }
    $aliases = Range::expand($hostname);

    foreach ($aliases as $alias) {
        if ($deployer->hosts->has($alias)) {
            $host = $deployer->hosts->get($alias);
            throw new \InvalidArgumentException("Host \"$host\" already exists.");
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
 * Define a local host. Commands run on the local machine instead of over SSH.
 *
 * ```php
 * localhost('ci'); // Alias and hostname will be "ci".
 * ```
 */
function localhost(string ...$hostnames): Localhost|ObjectProxy
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
 * Return the host the current task is running on.
 *
 * ```php
 * task('whoami', function () {
 *     writeln(currentHost()->getAlias());
 * });
 * ```
 */
function currentHost(): Host
{
    return Context::get()->getHost();
}

/**
 * Return hosts matching a selector expression.
 *
 * ```php
 * on(select('stage=prod, role=db'), function (Host $host) {
 *     // Runs on hosts tagged stage=prod AND role=db.
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
 * Return the hosts the user picked on the command line.
 *
 * @return Host[]
 */
function selectedHosts(): array
{
    $hosts = [];
    foreach (get('selected_hosts', []) as $alias) {
        $hosts[] = Deployer::get()->hosts->get($alias);
    }
    return $hosts;
}

/**
 * Import another recipe (PHP or MAML).
 *
 * Built-in `recipe/*` and `contrib/*` paths are already on PHP's include path,
 * so they can be imported by relative path. Use `__DIR__` for files inside
 * your own project.
 *
 * ```php
 * import('recipe/common.php');                  // built-in recipe
 * import('contrib/rsync.php');                  // contrib recipe
 * import(__DIR__ . '/config/hosts.maml');       // local file
 * ```
 */
function import(string $file): void
{
    Import::import($file);
}

/**
 * Set the description for the next task defined with `task()`.
 *
 * ```php
 * desc('Restart php-fpm');
 * task('restart', function () {
 *     run('sudo systemctl restart php-fpm');
 * });
 * ```
 *
 * Calling `desc()` with no argument returns the pending description.
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
 * Define a task, or return an already defined one.
 *
 * Pass a callback to define a single task:
 * ```php
 * task('deploy:run', function () {
 *     run('echo deploying');
 * });
 * ```
 *
 * Pass an array of task names to define a group task:
 * ```php
 * task('deploy', ['deploy:update_code', 'deploy:vendors', 'deploy:symlink']);
 * ```
 *
 * Pass only the name to fetch an existing task:
 * ```php
 * task('deploy')->desc('Run full deploy');
 * ```
 *
 * @param string $name Task name.
 * @param callable|array|null $body Callback, list of task names, or null to fetch an existing task.
 * @return Task
 */
function task(string $name, callable|array|null $body = null): Task
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
        throw new \InvalidArgumentException('Task body should be a function or an array.');
    }

    if ($deployer->tasks->has($name)) {
        // If task already exists, try to replace.
        $existingTask = $deployer->tasks->get($name);
        if (get_class($existingTask) !== get_class($task)) {
            // There is no "up" or "down"casting in PHP.
            throw new \Exception('Tried to replace Task \'' . $name . '\' with a GroupTask or vice-versa. This is not supported. If you are sure you want to do that, remove the old task `Deployer::get()->tasks->remove(<taskname>)` and then re-add the task.');
        }
        if ($existingTask instanceof GroupTask) {
            $existingTask->setGroup($body);
        } elseif ($existingTask instanceof Task) {
            $existingTask->setCallback($body);
        }
        $task = $existingTask;
    } else {
        // If task does not exist, add it to the Collection.
        $deployer->tasks->set($name, $task);
    }

    $task->saveSourceLocation();

    if (!empty(desc())) {
        $task->desc(desc());
        desc(''); // Clear title.
    }

    return $task;
}

/**
 * Run a task (or callback) before another task.
 *
 * ```php
 * before('deploy:symlink', 'deploy:cache:warmup');
 *
 * before('deploy:symlink', function () {
 *     run('echo about to symlink');
 * });
 * ```
 *
 * @param string $task Name of the task to attach the hook to.
 * @param string|callable $do Task name or callback to run.
 *
 * @return ?Task
 */
function before(string $task, string|callable $do): ?Task
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
 * Run a task (or callback) after another task.
 *
 * ```php
 * after('deploy:symlink', 'deploy:cleanup');
 *
 * after('deploy:failed', function () {
 *     run('echo something went wrong');
 * });
 * ```
 *
 * @param string $task Name of the task to attach the hook to.
 * @param string|callable $do Task name or callback to run.
 *
 * @return ?Task
 */
function after(string $task, string|callable $do): ?Task
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
 * Run a task (or callback) when another task fails.
 *
 * Calling `fail()` again for the same task replaces the previous handler.
 *
 * ```php
 * fail('deploy', 'deploy:unlock');
 *
 * fail('deploy', function () {
 *     run('echo rollback triggered');
 * });
 * ```
 *
 * @param string $task Name of the task whose failure triggers `$do`.
 * @param string|callable $do Task name or callback to run on failure.
 *
 * @return ?Task
 */
function fail(string $task, string|callable $do): ?Task
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
 * Add a CLI option to the `dep` binary.
 *
 * ```php
 * use Symfony\Component\Console\Input\InputOption;
 *
 * option('tag', null, InputOption::VALUE_REQUIRED, 'Release tag');
 *
 * task('deploy', function () {
 *     $tag = input()->getOption('tag');
 * });
 * ```
 *
 * @param string $name Option name (long form, no leading dashes).
 * @param string|array|null $shortcut Single-letter shortcut, `|`-separated list, or array of shortcuts.
 * @param int|null $mode One of the `InputOption::VALUE_*` constants.
 * @param string $description Help text shown in `dep --help`.
 * @param string|string[]|int|bool|null $default Default value (must be null for `VALUE_NONE`).
 */
function option(string $name, $shortcut = null, ?int $mode = null, string $description = '', $default = null): void
{
    Deployer::get()->inputDefinition->addOption(
        new InputOption($name, $shortcut, $mode, $description, $default),
    );
}

/**
 * Change the current working directory.
 *
 * Both `cd()` and the `cwd:` argument of `run()` change the working directory
 * for executed commands. The difference: `cd()` changes it for the rest of the
 * current task, while `cwd:` overrides it for a single `run()` call only.
 *
 * ```php
 * set('deploy_path', '~/deployer.org');
 *
 * task('task1', function () {
 *     cd('{{deploy_path}}');
 *
 *     run('pwd');
 *     // output: /home/deployer/deployer.org
 *
 *     run('pwd', cwd: '/usr'); // Override working dir for this run only.
 *     // output: /usr
 *
 *     run('pwd');
 *     // output: /home/deployer/deployer.org
 * });
 * ```
 *
 * Note that `cd()` only changes the working directory within a single task.
 * The next task starts fresh.
 *
 * ```php
 * task('task2', function () {
 *     run('pwd'); // cd from previous task is not used.
 *     // output: /home/deployer
 * });
 *
 * task('all', [
 *    'task1',
 *    'task2',
 * ]);
 * ```
 */
function cd(string $path): void
{
    set('working_path', parse($path));
}

/**
 * Switch the user that `run()` uses for subsequent commands.
 *
 * Returns a closure that restores the previous user when called.
 *
 * ```php
 * $restore = become('deployer');
 * run('whoami'); // deployer
 * $restore();
 * ```
 *
 * @return \Closure Restores the previous user when invoked.
 */
function become(string $user): \Closure
{
    $currentBecome = get('become');
    set('become', $user);
    return function () use ($currentBecome) {
        set('become', $currentBecome);
    };
}

/**
 * Run a callback inside a working directory, then restore the previous one.
 *
 * Use this when you need a scoped `cd()` that does not leak to the rest of the task.
 *
 * ```php
 * within('{{release_path}}', function () {
 *     run('composer install');
 * });
 * ```
 *
 * @return mixed Whatever `$callback` returns, or null.
 * @throws Exception
 */
function within(string $path, callable $callback): mixed
{
    $lastWorkingPath = get('working_path', '');
    try {
        set('working_path', parse($path));
        return $callback();
    } finally {
        set('working_path', $lastWorkingPath);
    }
}

/**
 * Run a command on the current remote host and return its trimmed stdout.
 *
 * ```php
 * run('echo hello world');
 * run('cd {{deploy_path}} && git status');
 * run('curl medv.io', timeout: 5);
 *
 * $path = run('readlink {{deploy_path}}/current');
 * run("echo $path");
 * ```
 *
 * Pass secrets via placeholders (e.g. `%token%`) so they are redacted in logs:
 * ```php
 * run('curl -u admin:%token% https://api.example', secrets: ['token' => getenv('TOKEN')]);
 * ```
 *
 * Use the `| quote` filter to escape config values as shell arguments:
 * ```php
 * run('echo {{ message | quote }}');
 * run('grep -r {{ pattern | quote }} {{release_path}}');
 * ```
 *
 * To emit a literal `{{`, escape it with a backslash:
 * ```php
 * run('echo \{{not_replaced}}'); // outputs: {{not_replaced}}
 * ```
 *
 * @param string $command Command to run on the remote host.
 * @param string|null $cwd Working directory for this run. Defaults to `{{working_path}}` (set by `cd()`).
 * @param int|null $timeout Max runtime in seconds (default: `{{default_timeout}}`, 300; `null` disables).
 * @param int|null $idleTimeout Max seconds without output before aborting.
 * @param array|null $secrets Map of `%name%` placeholders to redacted values.
 * @param array|null $env Environment variables: `run('echo $KEY', env: ['KEY' => 'value']);`
 * @param bool|null $forceOutput Print command output in real time.
 * @param bool|null $nothrow Return output instead of throwing on non-zero exit.
 * @throws RunException
 * @throws TimeoutException
 * @throws WillAskUser
 */
function run(
    string  $command,
    ?string $cwd = null,
    ?array  $env = null,
    #[\SensitiveParameter]
    ?array  $secrets = null,
    ?bool   $nothrow = false,
    ?bool   $forceOutput = false,
    ?int    $timeout = null,
    ?int    $idleTimeout = null,
): string {
    $runParams = new RunParams(
        shell: currentHost()->getShell(),
        cwd: $cwd ?? (has('working_path') ? get('working_path') : null),
        env: array_merge_alternate(get('env', []), $env ?? []),
        nothrow: $nothrow,
        timeout: $timeout ?? get('default_timeout', 300),
        idleTimeout: $idleTimeout,
        forceOutput: $forceOutput,
        secrets: $secrets,
    );

    $dotenv = get('dotenv', false);
    if (!empty($dotenv)) {
        $runParams->dotenv = $dotenv;
    }

    $run = function (string $command, ?RunParams $params = null) use ($runParams): string {
        $params = $params ?? $runParams;
        $host = currentHost();
        $command = parse($command);
        if ($host instanceof Localhost) {
            $process = Deployer::get()->processRunner;
            $output = $process->run($host, $command, $params);
        } else {
            $client = Deployer::get()->sshClient;
            $output = $client->run($host, $command, $params);
        }
        return rtrim($output);
    };

    if (preg_match('/^sudo\b/', $command)) {
        try {
            return $run($command);
        } catch (RunException) {
            $askpass = get('sudo_askpass', '/tmp/dep_sudo_pass');
            $password = get('sudo_pass', false);
            if ($password === false) {
                writeln("<fg=green;options=bold>run</> $command");
                $password = askHiddenResponse(" [sudo] password for {{remote_user}}: ");
            }
            $run("echo -e '#!/bin/sh\necho \"\$PASSWORD\"' > $askpass");
            $run("chmod a+x $askpass");
            $command = preg_replace('/^sudo\b/', 'sudo -A', $command);
            $output = $run(" SUDO_ASKPASS=$askpass PASSWORD=%sudo_pass% $command", $runParams->with(
                secrets: ['sudo_pass' => quote($password)],
            ));
            $run("rm $askpass");
            return $output;
        }
    } else {
        return $run($command);
    }
}


/**
 * Run a command on the local machine and return its trimmed stdout.
 *
 * ```php
 * $branch = runLocally('git rev-parse --abbrev-ref HEAD');
 * runLocally('npm run build', timeout: 600);
 * ```
 *
 * @param string $command Command to run locally.
 * @param string|null $cwd Working directory for this run. Defaults to `{{working_path}}`.
 * @param int|null $timeout Max runtime in seconds (default 300, `null` disables).
 * @param int|null $idleTimeout Max seconds without output before aborting.
 * @param array|null $secrets Map of `%name%` placeholders to redacted values.
 * @param array|null $env Environment variables: `runLocally('echo $KEY', env: ['KEY' => 'value']);`
 * @param bool|null $forceOutput Print command output in real time.
 * @param bool|null $nothrow Return output instead of throwing on non-zero exit.
 * @param string|null $shell Shell to run in. Default `bash -s`.
 *
 * @throws RunException
 * @throws TimeoutException
 */
function runLocally(
    string  $command,
    ?string $cwd = null,
    ?int    $timeout = null,
    ?int    $idleTimeout = null,
    #[\SensitiveParameter]
    ?array  $secrets = null,
    ?array  $env = null,
    ?bool   $forceOutput = false,
    ?bool   $nothrow = false,
    ?string $shell = null,
): string {
    $runParams = new RunParams(
        shell: $shell ?? 'bash -s',
        cwd: $cwd,
        env: $env,
        nothrow: $nothrow,
        timeout: $timeout,
        idleTimeout: $idleTimeout,
        forceOutput: $forceOutput,
        secrets: $secrets,
    );

    $process = Deployer::get()->processRunner;
    $command = parse($command);

    $output = $process->run(new Localhost(), $command, $runParams);
    return rtrim($output);
}

/**
 * Return whether a shell test command succeeds on the remote host.
 *
 * ```php
 * if (test('[ -d {{release_path}} ]')) {
 *     run('rm -rf {{release_path}}');
 * }
 * ```
 */
function test(string $command): bool
{
    $true = '+' . array_rand(array_flip(['accurate', 'appropriate', 'correct', 'legitimate', 'precise', 'right', 'true', 'yes', 'indeed']));
    return trim(run("if $command; then echo $true; fi")) === $true;
}

/**
 * Return whether a shell test command succeeds on the local machine.
 *
 * ```php
 * if (testLocally('[ -f .env ]')) {
 *     upload('.env', '{{release_path}}/.env');
 * }
 * ```
 */
function testLocally(string $command): bool
{
    return runLocally("if $command; then echo +true; fi") === '+true';
}

/**
 * Run a callback on each given host.
 *
 * ```php
 * on(select('stage=prod, role=db'), function (Host $host) {
 *     run('mysqldump app > /tmp/backup.sql');
 * });
 *
 * on(host('example.org'), function (Host $host) {
 *     run('uptime');
 * });
 *
 * on(Deployer::get()->hosts, function (Host $host) {
 *     run('uname -a');
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
            Context::push(new Context($host));
            try {
                $callback($host);
                $host->config()->save();
            } catch (GracefulShutdownException $e) {
                Deployer::get()->logger->renderException($e, $host);
            } finally {
                Context::pop();
            }
        } else {
            throw new \InvalidArgumentException("Function on can iterate only on Host instances.");
        }
    }
}

/**
 * Run another task by name from inside the current task.
 *
 * ```php
 * task('deploy', function () {
 *     invoke('deploy:update_code');
 *     invoke('deploy:symlink');
 * });
 * ```
 *
 * @throws Exception
 */
function invoke(string $taskName): void
{
    $task = Deployer::get()->tasks->get($taskName);
    Deployer::get()->logger->startTask($task);
    $task->run(Context::get());
    Deployer::get()->logger->endTask($task);
}

/**
 * Upload files or directories to the current host via rsync.
 *
 * To copy the *contents* of a directory, end the source with `/`:
 * ```php
 * upload('build/', '{{release_path}}/public'); // copies contents of build/
 * upload('build',  '{{release_path}}/public'); // copies the build/ dir itself
 * ```
 *
 * `$config` keys:
 * - `flags`: replaces the default `-azP` flags
 * - `options`: extra flags appended to the rsync command
 * - `timeout`: process timeout in seconds (`null` = no limit)
 * - `progress_bar`: show transfer progress
 * - `display_stats`: show rsync statistics
 *
 * Note: PHP shell-escaping breaks rsync's `--exclude={'a','b'}` brace list.
 * Pass each exclude as its own `--exclude=...` option, or use `--exclude-from=<file>`.
 *
 * @param string|string[] $source
 * @param array $config
 * @phpstan-param array{flags?: string, options?: array, timeout?: int|null, progress_bar?: bool, display_stats?: bool} $config
 *
 * @throws RunException
 */
function upload($source, string $destination, array $config = []): void
{
    $rsync = Deployer::get()->rsync;
    $host = currentHost();
    $source = is_array($source) ? array_map('Deployer\parse', $source) : parse($source);
    $destination = parse($destination);

    if ($host instanceof Localhost) {
        $rsync->call($host, $source, $destination, $config);
    } else {
        $rsync->call($host, $source, "{$host->connectionString()}:$destination", $config);
    }
}

/**
 * Download a file or directory from the current host via rsync.
 *
 * ```php
 * download('{{deploy_path}}/.dep/database.sql', 'backup/database.sql');
 * ```
 *
 * `$config` accepts the same keys as `upload()`.
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
        $rsync->call($host, "{$host->connectionString()}:$source", $destination, $config);
    }
}

/**
 * Print an info message, prefixed with `info` and the host alias.
 *
 * ```php
 * info('Deployed release {{release_name}}');
 * ```
 */
function info(string $message): void
{
    writeln("<fg=green;options=bold>info</> " . parse($message));
}

/**
 * Print a warning message.
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
 * Write a line to the output, prefixed with the current host alias.
 *
 * The message is parsed for `{{config}}` placeholders before printing.
 */
function writeln(string $message, int $options = 0): void
{
    $host = currentHost();
    output()->writeln("[$host] " . parse($message), $options);
}

/**
 * Replace `{{name}}` placeholders in a string with values from the current config.
 *
 * ```php
 * $current = parse('{{deploy_path}}/current');
 * ```
 */
function parse(string $value): string
{
    return Context::get()->getConfig()->parse($value);
}

/**
 * Set a [global config](https://deployer.org/docs/8.x/basics#global-configurations) value.
 *
 * ```php
 * set('keep_releases', 5);
 * set('shared_files', ['.env']);
 * ```
 *
 * @param mixed $value
 * @throws Exception
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
 * Append values to an array config option.
 *
 * ```php
 * add('shared_files', ['.env']);
 * add('shared_dirs', ['storage/logs']);
 * ```
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
 * Return a config value, or `$default` if it isn't set.
 *
 * Callable values are resolved on first access and cached.
 *
 * ```php
 * $branch = get('branch', 'main');
 * ```
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
 * Return whether a config option is set.
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
 * Prompt the user for a string, on the current host.
 *
 * Returns `$default` in quiet mode (`-q`).
 *
 * ```php
 * $branch = ask('Branch to deploy?', 'main');
 * $tag    = ask('Tag?', null, ['v1.0', 'v1.1', 'v1.2']);
 * ```
 */
function ask(string $message, ?string $default = null, ?array $autocomplete = null): ?string
{
    if (WillAskUser::$noAsk) {
        throw new WillAskUser($message);
    }
    Context::required(__FUNCTION__);

    if (output()->isQuiet()) {
        return $default;
    }

    if (Deployer::isWorker()) {
        return Deployer::masterCall(currentHost(), __FUNCTION__, ...func_get_args());
    }

    /** @var QuestionHelper */
    $helper = Deployer::get()->getHelper('question');

    $tag = currentHost()->getTag();
    $message = parse($message);
    $message = "[$tag] <question>$message</question> " . (($default === null) ? "" : "(default: $default) ");

    $question = new Question($message, $default);
    if (!empty($autocomplete)) {
        $question->setAutocompleterValues($autocomplete);
    }

    return $helper->ask(input(), output(), $question);
}

/**
 * Prompt the user to pick one or more values from a list.
 *
 * ```php
 * $color = askChoice('Pick a color', ['red', 'green', 'blue'], 0);
 * ```
 *
 * @param mixed $default Key into `$availableChoices`, or null for no default.
 * @return mixed Selected value (or array of values when `$multiselect` is true).
 * @throws Exception
 */
function askChoice(string $message, array $availableChoices, $default = null, bool $multiselect = false)
{
    if (WillAskUser::$noAsk) {
        throw new WillAskUser($message);
    }
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
        return Deployer::masterCall(currentHost(), __FUNCTION__, ...func_get_args());
    }

    /** @var QuestionHelper */
    $helper = Deployer::get()->getHelper('question');

    $tag = currentHost()->getTag();
    $message = parse($message);
    $message = "[$tag] <question>$message</question> " . (($default === null) ? "" : "(default: $default) ");

    $question = new ChoiceQuestion($message, $availableChoices, $default);
    $question->setMultiselect($multiselect);

    return $helper->ask(input(), output(), $question);
}

/**
 * Prompt the user with a yes/no question. Returns `$default` in quiet mode.
 *
 * ```php
 * if (askConfirmation('Drop the database?')) {
 *     run('mysql -e "DROP DATABASE app"');
 * }
 * ```
 */
function askConfirmation(string $message, bool $default = false): bool
{
    if (WillAskUser::$noAsk) {
        throw new WillAskUser($message);
    }
    Context::required(__FUNCTION__);

    if (output()->isQuiet()) {
        return $default;
    }

    if (Deployer::isWorker()) {
        return Deployer::masterCall(currentHost(), __FUNCTION__, ...func_get_args());
    }

    /** @var QuestionHelper */
    $helper = Deployer::get()->getHelper('question');

    $yesOrNo = $default ? 'Y/n' : 'y/N';
    $tag = currentHost()->getTag();
    $message = parse($message);
    $message = "[$tag] <question>$message</question> [$yesOrNo] ";

    $question = new ConfirmationQuestion($message, $default);

    return $helper->ask(input(), output(), $question);
}

/**
 * Prompt the user for input without echoing it. Use for passwords and tokens.
 */
function askHiddenResponse(string $message): string
{
    if (WillAskUser::$noAsk) {
        throw new WillAskUser($message);
    }
    Context::required(__FUNCTION__);

    if (output()->isQuiet()) {
        return '';
    }

    if (Deployer::isWorker()) {
        return (string) Deployer::masterCall(currentHost(), __FUNCTION__, ...func_get_args());
    }

    /** @var QuestionHelper */
    $helper = Deployer::get()->getHelper('question');

    $tag = currentHost()->getTag();
    $message = parse($message);
    $message = "[$tag] <question>$message</question> ";

    $question = new Question($message);
    $question->setHidden(true);
    $question->setHiddenFallback(false);

    return (string) $helper->ask(input(), output(), $question);
}

/**
 * Return the Symfony Console input, e.g. to read CLI options.
 *
 * ```php
 * $tag = input()->getOption('tag');
 * ```
 */
function input(): InputInterface
{
    return Deployer::get()->input;
}

/**
 * Return the Symfony Console output, e.g. to check verbosity.
 */
function output(): OutputInterface
{
    return Deployer::get()->output;
}

/**
 * Return whether a command is available on the current host's PATH.
 *
 * ```php
 * if (!commandExist('git')) {
 *     throw error('git is required to deploy');
 * }
 * ```
 *
 * @throws RunException
 */
function commandExist(string $command): bool
{
    return test("hash $command 2>/dev/null");
}

/**
 * Return whether a command's man page or `--help` output mentions the given option.
 *
 * Useful for picking newer flags only when the installed version supports them.
 *
 * ```php
 * $progress = commandSupportsOption('rsync', '--info=progress2') ? '--info=progress2' : '--progress';
 * ```
 *
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
 * Return the absolute path of a command on the current host.
 *
 * Tries `command -v`, then `which`, then `type -p`.
 *
 * ```php
 * $php = which('php');
 * run("$php -v");
 * ```
 *
 * @throws RunException
 * @throws \RuntimeException If the command is not found.
 */
function which(string $name): string
{
    $nameQuoted = quote($name);

    // Try `command`, should cover all Bourne-like shells
    // Try `which`, should cover most other cases
    // Fallback to `type` command, if the rest fails
    $path = run("command -v $nameQuoted || which $nameQuoted || type -p $nameQuoted");
    if (empty($path)) {
        throw new \RuntimeException("Can't locate [$nameQuoted] - neither of [command|which|type] commands are available");
    }

    // Deal with issue when `type -p` outputs something like `type -ap` in some implementations
    return trim(str_replace("$name is", "", $path));

}

/**
 * Return the remote host's environment as an associative array.
 *
 * ```php
 * $remotePath = remoteEnv()['PATH'];
 * run('echo $PATH', env: ['PATH' => "/home/user/bin:$remotePath"]);
 * ```
 */
function remoteEnv(): array
{
    $vars = [];
    $data = run('env');
    foreach (explode("\n", $data) as $line) {
        [$name, $value] = explode('=', $line, 2);
        $vars[$name] = $value;
    }
    return $vars;
}

/**
 * Build a Deployer `Exception` with `{{config}}` placeholders parsed in the message.
 *
 * ```php
 * if (!commandExist('git')) {
 *     throw error('git is required on {{alias}}');
 * }
 * ```
 */
function error(string $message): Exception
{
    return new Exception(parse($message));
}

/**
 * Return the current UTC timestamp in ISO 8601 format.
 */
function timestamp(): string
{
    return (new \DateTime('now', new \DateTimeZone('UTC')))->format(\DateTime::ISO8601);
}

/**
 * Quote a string for safe use as a shell argument (ANSI-C `$'...'` syntax).
 *
 * Strings made of safe characters (alphanumeric, `/.-+@:=,%`) are returned unquoted.
 * Throws on null bytes.
 *
 * ```php
 * run('git log --format=' . quote($format));
 * run('echo ' . quote("it's a test"));  // echo $'it\'s a test'
 * ```
 */
function quote(string|int $arg): string
{
    $arg = (string) $arg;
    if ($arg === '') {
        return "\$''";
    }
    if (str_contains($arg, "\0")) {
        throw new \InvalidArgumentException('quote(): null byte is not allowed in shell arguments');
    }
    if (preg_match('/^[\w\/.\-+@:=,%]+$/', $arg)) {
        return $arg;
    }
    return "\$'" . strtr($arg, [
        '\\' => '\\\\',
        "'" => "\\'",
        "\f" => '\\f',
        "\n" => '\\n',
        "\r" => '\\r',
        "\t" => '\\t',
        "\v" => '\\v',
    ]) . "'";
}

/**
 * Make an HTTP request and return the response body.
 *
 * Pass `$info` by reference to capture status code, headers, and timing
 * (same shape as PHP's `curl_getinfo`). Use `nothrow: true` to receive the
 * body even on non-2xx responses.
 *
 * ```php
 * $body = fetch('{{domain}}/health', info: $info);
 * if ($info['http_code'] !== 200) {
 *     throw error("health check failed: {$info['http_code']}");
 * }
 *
 * fetch('https://api.example.com/notify', 'post',
 *     ['Authorization' => 'Bearer {{token}}'],
 *     json_encode(['release' => '{{release_name}}']),
 * );
 * ```
 */
function fetch(string $url, string $method = 'get', array $headers = [], ?string $body = null, ?array &$info = null, bool $nothrow = false): string
{
    $url = parse($url);
    $http = match (strtolower($method)) {
        'get' => Httpie::get($url),
        'post' => Httpie::post($url),
        'put' => Httpie::put($url),
        'patch' => Httpie::patch($url),
        'delete' => Httpie::delete($url),
        default => throw new \InvalidArgumentException("Unknown method \"$method\"."),
    };
    $http = $http->nothrow($nothrow);
    foreach ($headers as $key => $value) {
        $http = $http->header($key, $value);
    }
    if ($body !== null) {
        $http = $http->body($body);
    }
    return $http->send($info)->body();
}
