<?php declare(strict_types=1);

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
use Deployer\Component\ProcessRunner\Printer;
use Deployer\Component\ProcessRunner\ProcessRunner;
use Deployer\Component\Ssh\Client;
use Deployer\Configuration\Configuration;
use Deployer\Executor\Master;
use Deployer\Executor\Messenger;
use Deployer\Executor\Server;
use Deployer\Host\Host;
use Deployer\Host\HostCollection;
use Deployer\Host\Localhost;
use Deployer\Importer\Importer;
use Deployer\Logger\Handler\FileHandler;
use Deployer\Logger\Handler\NullHandler;
use Deployer\Logger\Logger;
use Deployer\Selector\Selector;
use Deployer\Task;
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
 * Deployer class represents DI container for configuring
 *
 * @property Application $console
 * @property InputInterface $input
 * @property OutputInterface $output
 * @property Task\TaskCollection|Task\Task[] $tasks
 * @property HostCollection|Host[] $hosts
 * @property Configuration $config
 * @property Rsync $rsync
 * @property Client $sshClient
 * @property ProcessRunner $processRunner
 * @property Task\ScriptManager $scriptManager
 * @property Selector $selector
 * @property Server $server
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
    /**
     * Global instance of deployer. It's can be accessed only after constructor call.
     * @var Deployer
     */
    private static $instance;

    public function __construct(Application $console)
    {
        parent::__construct();

        /******************************
         *           Console          *
         ******************************/

        $console->getDefinition()->addOption(
            new InputOption('file', 'f', InputOption::VALUE_REQUIRED, 'Recipe file path')
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
            return new Client($c['output'], $c['pop'], $c['logger']);
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
        $this['server'] = function ($c) {
            return new Server(
                $c['output'],
                $this,
            );
        };
        $this['master'] = function ($c) {
            return new Master(
                $c['input'],
                $c['output'],
                $c['server'],
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

    /**
     * Init console application
     */
    public function init()
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
    public function addTaskCommands()
    {
        foreach ($this->tasks as $name => $task) {
            $command = new MainCommand($name, $task->getDescription(), $this);
            $command->setHidden($task->isHidden());

            $this->getConsole()->add($command);
        }
    }

    /**
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function __get(string $name)
    {
        if (isset($this[$name])) {
            return $this[$name];
        } else {
            throw new \InvalidArgumentException("Property \"$name\" does not exist.");
        }
    }

    /**
     * @param mixed $value
     */
    public function __set(string $name, $value)
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

    /**
     * Run Deployer
     */
    public static function run(string $version, ?string $deployFile)
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
            // Init Deployer
            $console = new Application('Deployer', $version);
            $deployer = new self($console);

            // Import recipe file
            if (is_readable($deployFile ?? '')) {
                $deployer->importer->import($deployFile);
            }

            // Run Deployer
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

    public static function printException(OutputInterface $output, Throwable $exception)
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
        return Deployer::get()->config->has('master_url');
    }

    /**
     * @param mixed ...$arguments
     * @return array|bool|string
     * @throws \Exception
     */
    public static function proxyCallToMaster(Host $host, string $func, ...$arguments)
    {
        // As request to master will stop master permanently,
        // wait a little bit in order for periodic timer of
        // master gather worker outputs and print it to user.
        usleep(100000); // Sleep 100ms.
        return Httpie::get(get('master_url') . '/proxy')
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
        return 'phar:' === substr(__FILE__, 0, 5);
    }
}
