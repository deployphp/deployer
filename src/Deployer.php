<?php

declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Collection\Collection;
use Deployer\Command\BlackjackCommand;
use Deployer\Command\ConfigCommand;
use Deployer\Command\InitCommand;
use Deployer\Command\MainCommand;
use Deployer\Command\RunCommand;
use Deployer\Command\SshCommand;
use Deployer\Command\TreeCommand;
use Deployer\Command\WorkerCommand;
use Deployer\Component\PharUpdate\Console\Command as PharUpdateCommand;
use Deployer\Component\PharUpdate\Console\Helper as PharUpdateHelper;
use Deployer\Component\Pimple\Container;
use Deployer\ProcessRunner\Printer;
use Deployer\ProcessRunner\ProcessRunner;
use Deployer\Ssh\SshClient;
use Deployer\Configuration\Configuration;
use Deployer\Executor\Master;
use Deployer\Executor\Messenger;
use Deployer\Host\Host;
use Deployer\Host\HostCollection;
use Deployer\Host\Localhost;
use Deployer\Importer\Importer;
use Deployer\Logger\Handler\FileHandler;
use Deployer\Logger\Handler\NullHandler;
use Deployer\Logger\Logger;
use Deployer\Selector\Selector;
use Deployer\Task\ScriptManager;
use Deployer\Task\TaskCollection;
use Deployer\Utility\Httpie;
use Deployer\Utility\Rsync;
use Symfony\Component\Console;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * @property Application $console
 * @property InputInterface $input
 * @property OutputInterface $output
 * @property Task\TaskCollection|Task\Task[] $tasks
 * @property HostCollection|Host[] $hosts
 * @property Configuration $config
 * @property Rsync $rsync
 * @property SshClient $sshClient
 * @property ProcessRunner $processRunner
 * @property Task\ScriptManager $scriptManager
 * @property Selector $selector
 * @property Master $master
 * @property Messenger $messenger
 * @property Messenger $logger
 * @property Printer $pop
 * @property Collection $fail
 * @property InputDefinition $inputDefinition
 * @property Importer $importer
 */
class Deployer extends Container
{
    private static Deployer $instance;

    public function __construct(Application $console)
    {
        parent::__construct();

        /******************************
         *           Console          *
         ******************************/

        $console->getDefinition()->addOption(
            new InputOption('file', 'f', InputOption::VALUE_REQUIRED, 'Recipe file path'),
        );

        $this['console'] = function () use ($console) {
            return $console;
        };
        $this['input'] = function () {
            throw new \RuntimeException('Uninitialized "input" in Deployer container.');
        };
        $this['output'] = function () {
            throw new \RuntimeException('Uninitialized "output" in Deployer container.');
        };
        $this['inputDefinition'] = function () {
            return new InputDefinition();
        };
        $this['questionHelper'] = function () {
            return $this->getHelper('question');
        };

        /******************************
         *           Config           *
         ******************************/

        $this['config'] = function () {
            return new Configuration();
        };
        // -l  act as if it had been invoked as a login shell (i.e. source ~/.profile file)
        // -s  commands are read from the standard input (no arguments should remain after this option)
        $this->config['shell'] = function () {
            if (currentHost() instanceof Localhost) {
                return 'bash -s'; // Non-login shell for localhost.
            }
            return 'bash -ls';
        };
        $this->config['forward_agent'] = true;
        $this->config['ssh_multiplexing'] = true;

        /******************************
         *            Core            *
         ******************************/

        $this['pop'] = function ($c) {
            return new Printer($c['output']);
        };
        $this['sshClient'] = function ($c) {
            return new SshClient($c['output'], $c['pop'], $c['logger']);
        };
        $this['rsync'] = function ($c) {
            return new Rsync($c['pop'], $c['output']);
        };
        $this['processRunner'] = function ($c) {
            return new ProcessRunner($c['pop'], $c['logger']);
        };
        $this['tasks'] = function () {
            return new TaskCollection();
        };
        $this['hosts'] = function () {
            return new HostCollection();
        };
        $this['scriptManager'] = function ($c) {
            return new ScriptManager($c['tasks']);
        };
        $this['selector'] = function ($c) {
            return new Selector($c['hosts']);
        };
        $this['fail'] = function () {
            return new Collection();
        };
        $this['messenger'] = function ($c) {
            return new Messenger($c['input'], $c['output'], $c['logger']);
        };
        $this['master'] = function ($c) {
            return new Master(
                $c['hosts'],
                $c['input'],
                $c['output'],
                $c['messenger'],
            );
        };
        $this['importer'] = function () {
            return new Importer();
        };

        /******************************
         *           Logger           *
         ******************************/

        $this['log_handler'] = function () {
            return !empty($this['log'])
                ? new FileHandler($this['log'])
                : new NullHandler();
        };
        $this['logger'] = function () {
            return new Logger($this['log_handler']);
        };

        self::$instance = $this;
    }

    public static function get(): self
    {
        return self::$instance;
    }

    public function init(): void
    {
        $this->addTaskCommands();
        $this->getConsole()->add(new BlackjackCommand());
        $this->getConsole()->add(new ConfigCommand($this));
        $this->getConsole()->add(new WorkerCommand($this));
        $this->getConsole()->add(new InitCommand());
        $this->getConsole()->add(new TreeCommand($this));
        $this->getConsole()->add(new SshCommand($this));
        $this->getConsole()->add(new RunCommand($this));
        if (self::isPharArchive()) {
            $selfUpdate = new PharUpdateCommand('self-update');
            $selfUpdate->setDescription('Updates deployer.phar to the latest version');
            $selfUpdate->setManifestUri('https://deployer.org/manifest.json');
            $selfUpdate->setRunningFile(DEPLOYER_BIN);
            $this->getConsole()->add($selfUpdate);
            $this->getConsole()->getHelperSet()->set(new PharUpdateHelper());
        }
    }

    /**
     * Transform tasks to console commands.
     */
    public function addTaskCommands(): void
    {
        foreach ($this->tasks as $name => $task) {
            $command = new MainCommand($name, $task->getDescription(), $this);
            $command->setHidden($task->isHidden());

            $this->getConsole()->add($command);
        }
    }

    public function __get(string $name): mixed
    {
        if (isset($this[$name])) {
            return $this[$name];
        } else {
            throw new \InvalidArgumentException("Property \"$name\" does not exist.");
        }
    }

    public function __set(string $name, mixed $value): void
    {
        $this[$name] = $value;
    }

    public function getConsole(): Application
    {
        return $this['console'];
    }

    public function getHelper(string $name): Console\Helper\HelperInterface
    {
        return $this->getConsole()->getHelperSet()->get($name);
    }

    public static function run(string $version, ?string $deployFile): void
    {
        if (str_contains($version, 'master')) {
            // Get version from composer.lock
            $lockFile = __DIR__ . '/../../../../composer.lock';
            if (file_exists($lockFile)) {
                $content = file_get_contents($lockFile);
                $json = json_decode($content);
                foreach ($json->packages as $package) {
                    if ($package->name === 'deployer/deployer') {
                        $version = $package->version;
                    }
                }
            }
        }

        // Version must be without "v" prefix.
        //    Incorrect: v7.0.0
        //    Correct: 7.0.0
        // But deployphp/deployer uses tags with "v", and it gets passed to
        // the composer.json file. Let's manually remove it from the version.
        if (preg_match("/^v/", $version)) {
            $version = substr($version, 1);
        }

        if (!defined('DEPLOYER_VERSION')) {
            define('DEPLOYER_VERSION', $version);
        }

        $input = new ArgvInput();
        $output = new ConsoleOutput();

        try {
            $console = new Application('Deployer', $version);
            $deployer = new self($console);

            // Import recipe file
            if (is_readable($deployFile ?? '')) {
                $deployer->importer->import($deployFile);
            }

            $deployer->init();
            $console->run($input, $output);

        } catch (Throwable $exception) {
            if (str_contains("$input", "-vvv")) {
                $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
            }
            self::printException($output, $exception);

            exit(1);
        }
    }

    public static function printException(OutputInterface $output, Throwable $exception): void
    {
        $class = get_class($exception);
        $file = basename($exception->getFile());
        $output->writeln([
            "<fg=white;bg=red> {$class} </> <comment>in {$file} on line {$exception->getLine()}:</>",
            "",
            implode("\n", array_map(function ($line) {
                return "  " . $line;
            }, explode("\n", $exception->getMessage()))),
            "",
        ]);
        if ($output->isDebug()) {
            $output->writeln($exception->getTraceAsString());
        }

        if ($exception->getPrevious()) {
            self::printException($output, $exception->getPrevious());
        }
    }

    public static function isWorker(): bool
    {
        return defined('MASTER_ENDPOINT');
    }

    /**
     * @return array|bool|string
     */
    public static function masterCall(Host $host, string $func, mixed ...$arguments): mixed
    {
        // As request to master will stop master permanently, wait a little bit
        // in order for ticker gather worker outputs and print it to user.
        usleep(100_000); // Sleep 100ms.

        return Httpie::get(MASTER_ENDPOINT . '/proxy')
            ->setopt(CURLOPT_CONNECTTIMEOUT, 0) // no timeout
            ->setopt(CURLOPT_TIMEOUT, 0) // no timeout
            ->jsonBody([
                'host' => $host->getAlias(),
                'func' => $func,
                'arguments' => $arguments,
            ])
            ->getJson();
    }

    public static function isPharArchive(): bool
    {
        return str_starts_with(__FILE__, 'phar:');
    }
}
