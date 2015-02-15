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

// There are two types of functions: Deployer dependent and Context dependent.
// Deployer dependent function uses in definition stage of recipe and may require Deployer::get() method.
// Context dependent function uses while task execution and must require only Context::get() method.
// But there is also a third type of functions: mixed. Mixed function uses in definition stage and in task
// execution stage. They are acts like two different function, but have same name. Example of such function
// is env() func. This function determine in which stage it was called by Context::get() method.

/**
 * @param string $name
 * @param string $domain
 * @param int $port
 * @return Builder
 */
function server($name, $domain = null, $port = 22)
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

    if ($body instanceof \Closure) {
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
 * Run command on server.
 *
 * @param string $command
 * @return string
 */
function run($command)
{
    $server = Context::get()->getServer();
    $command = Context::get()->getEnvironment()->parse($command);

    if (isVeryVerbose()) {
        writeln("<comment>Run</comment>: $command");
    }

    $output = $server->run($command);

    if (isDebug() && !empty($output)) {
        writeln(array_map(function ($line) {
            return "<comment>#</comment> $line";
        }, explode("\n", $output)));
    }

    return $output;
}

/**
 * @param string $command
 * @return bool
 */
function runBool($command)
{
    $output = run($command);

    if ('true' === $output) {
        return true;
    } else {
        return false;
    }
}

/**
 * Execute commands on local machine.
 * @param string $command Command to run locally.
 * @return string Output of command.
 * @throws \RuntimeException
 */
function runLocally($command)
{
    $process = new Symfony\Component\Process\Process($command);
    $process->run();

    if (!$process->isSuccessful()) {
        throw new \RuntimeException($process->getErrorOutput());
    }

    write($process->getOutput());
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

    $remote = env()->get(Environment::DEPLOY_PATH) . '/' . $remote;

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

        /** @var $file \Symfony\Component\Finder\SplFileInfo */
        foreach ($files as $file) {
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
 * @param string $key
 * @param mixed $value
 */
function set($key, $value)
{
    Deployer::get()->parameters->set($key, $value);
}

/**
 * @param string $key
 * @return mixed
 */
function get($key)
{
    return Deployer::get()->parameters->get($key);
}

/**
 * @param string $message
 * @param string $default
 * @return string
 */
function ask($message, $default = null)
{
    if (isQuiet()) {
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
    if (isQuiet()) {
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
    if (isQuiet()) {
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
 * Return current server env or set default values or get env value.
 * When set env value you can write over values line "{name}".
 *
 * @param string $name
 * @param mixed $value
 * @return Environment|mixed
 */
function env($name = null, $value = null)
{
    if (false === Context::get()) {
        Environment::setDefault($name, $value);
    } else {
        if (null === $name && null === $value) {
            return Context::get()->getEnvironment();
        } else if (null !== $name && null === $value) {
            return Context::get()->getEnvironment()->get($name);
        } else {
            Context::get()->getEnvironment()->set($name, $value);
        }
        return null;
    }
}

/**
 * Adds a global argument
 *
 * @param string $name
 * @param array  $argument
 *
 * @return mixed
 */
function argument($name, array $argument = null)
{
    if (null === $argument) {
        return Deployer::get()->getInput()->getArgument($name);
    } else {
        Deployer::get()->addArgument($name, $argument);
    }
}

/**
 * Adds a global option
 *
 * @param string $name
 * @param array  $option
 *
 * @return mixed
 */
function option($name, array $option = null)
{
    if (null === $option) {
        return Deployer::get()->getInput()->getOption($name);
    } else {
        Deployer::get()->addOption($name, $option);
    }
}
