<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Collection\Collection;
use Deployer\Component\PharUpdate\Console\Command as PharUpdateCommand;
use Deployer\Component\PharUpdate\Console\Helper as PharUpdateHelper;
use Deployer\Component\ProcessRunner\Printer;
use Deployer\Component\ProcessRunner\ProcessRunner;
use Deployer\Component\Ssh\Client;
use Deployer\Configuration\Configuration;
use Deployer\Console\CommandEvent;
use Deployer\Console\DiceCommand;
use Deployer\Console\InitCommand;
use Deployer\Console\MainCommand;
use Deployer\Console\RunCommand;
use Deployer\Console\SshCommand;
use Deployer\Console\TreeCommand;
use Deployer\Console\WorkerCommand;
use Deployer\Executor\Messenger;
use Deployer\Executor\ParallelExecutor;
use Deployer\Logger\Handler\FileHandler;
use Deployer\Logger\Handler\NullHandler;
use Deployer\Logger\Logger;
use Deployer\Selector\Selector;
use Deployer\Task;
use Deployer\Utility\Reporter;
use Deployer\Utility\Rsync;
use Pimple\Container;
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
 * @property Host\HostCollection|Collection|Host\Host[] $hosts
 * @property Configuration $config
 * @property Rsync $rsync
 * @property Client $sshClient
 * @property ProcessRunner $processRunner
 * @property Task\ScriptManager $scriptManager
 * @property Selector $selector
 * @property ParallelExecutor $executor
 * @property Messenger $messenger
 * @property Messenger $logger
 * @property Printer $pop
 * @property Collection $fail
 * @property InputDefinition $inputDefinition
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
            new InputOption('--file', '-f', InputOption::VALUE_OPTIONAL, 'Specify Deployer file')
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

        /******************************
         *           Config           *
         ******************************/

        $this['config'] = function () {
            return new Configuration();
        };
        $this->config['ssh_multiplexing'] = true;
        $this->config['default_stage'] = null;

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
            return new Task\TaskCollection();
        };
        $this['hosts'] = function () {
            return new Host\HostCollection();
        };
        $this['scriptManager'] = function ($c) {
            return new Task\ScriptManager($c['tasks']);
        };
        $this['selector'] = function ($c) {
            return new Selector($c['hosts']);
        };
        $this['fail'] = function () {
            return new Collection();
        };
        $this['messenger'] = function ($c) {
            return new Messenger($c['input'], $c['output']);
        };
        $this['executor'] = function ($c) {
            return new ParallelExecutor(
                $c['input'],
                $c['output'],
                $c['messenger'],
                $c['sshClient'],
                $c['config']
            );
        };

        /******************************
         *           Logger           *
         ******************************/

        $this['log_handler'] = function () {
            return !empty($this->config['log_file'])
                ? new FileHandler($this->config['log_file'])
                : new NullHandler();
        };
        $this['logger'] = function () {
            return new Logger($this['log_handler']);
        };

        self::$instance = $this;

        task('connect', function () {
            $this['sshClient']->connect(currentHost());
        })->desc('Connect to remote server');
    }

    /**
     * @return Deployer
     */
    public static function get()
    {
        return self::$instance;
    }

    /**
     * Init console application
     */
    public function init()
    {
        $this->addTaskCommands();
        $this->getConsole()->add(new WorkerCommand($this));
        $this->getConsole()->add(new DiceCommand());
        $this->getConsole()->add(new InitCommand());
        $this->getConsole()->add(new TreeCommand($this));
        $this->getConsole()->add(new SshCommand($this));
        $this->getConsole()->add(new RunCommand($this));
        if (self::isPharArchive()) {
            $selfUpdate = new PharUpdateCommand('self-update');
            $selfUpdate->setDescription('Updates deployer.phar to the latest version');
            $selfUpdate->setManifestUri('https://deployer.org/manifest.json');
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
     * @param string $name
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function __get($name)
    {
        if (isset($this[$name])) {
            return $this[$name];
        } else {
            throw new \InvalidArgumentException("Property \"$name\" does not exist.");
        }
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this[$name] = $value;
    }

    /**
     * @return Application
     */
    public function getConsole()
    {
        return $this['console'];
    }

    /**
     * @param string $name
     * @return Console\Helper\HelperInterface
     */
    public function getHelper($name)
    {
        return $this->getConsole()->getHelperSet()->get($name);
    }

    /**
     * Run Deployer
     *
     * @param string $version
     * @param string $deployFile
     */
    public static function run($version, $deployFile)
    {
        $input = new ArgvInput();
        $output = new ConsoleOutput();

        try {
            // Init Deployer
            $console = new Application('Deployer', $version);
            $deployer = new self($console);

            // Require deploy.php file
            self::load($deployFile);

            // Run Deployer
            $deployer->init();
            $console->run($input, $output);

        } catch (Throwable $exception) {
            self::printException($output, $exception);
        }
    }

    public static function load(string $deployFile)
    {
        if (is_readable($deployFile)) {
            // Prevent variable leak into deploy.php file
            call_user_func(function () use ($deployFile) {
                // Reorder autoload stack
                $originStack = spl_autoload_functions();

                require $deployFile;

                $newStack = spl_autoload_functions();
                if ($originStack[0] !== $newStack[0]) {
                    foreach (array_reverse($originStack) as $loader) {
                        spl_autoload_unregister($loader);
                        spl_autoload_register($loader, true, true);
                    }
                }
            });
        }
    }

    private static function printException(OutputInterface $output, Throwable $exception)
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
        $output->writeln($exception->getTraceAsString());
        return;
    }

    /**
     * Collect anonymous stats about Deployer usage for improving developer experience.
     * If you are not comfortable with this, you will always be able to disable this
     * by setting `allow_anonymous_stats` to false in your deploy.php file.
     *
     * @param CommandEvent $commandEvent
     * @codeCoverageIgnore
     */
    public function collectAnonymousStats(CommandEvent $commandEvent)
    {
        if ($this->config->has('allow_anonymous_stats') && $this->config['allow_anonymous_stats'] === false) {
            return;
        }

        $stats = [
            'status' => 'success',
            'command_name' => $commandEvent->getCommand()->getName(),
            'project_hash' => empty($this->config['repository']) ? null : sha1($this->config['repository']),
            'hosts_count' => $this->hosts->count(),
            'deployer_version' => $this->getConsole()->getVersion(),
            'deployer_phar' => self::isPharArchive(),
            'php_version' => phpversion(),
            'extension_pcntl' => extension_loaded('pcntl'),
            'extension_curl' => extension_loaded('curl'),
            'os' => defined('PHP_OS_FAMILY') ? PHP_OS_FAMILY : (stristr(PHP_OS, 'DAR') ? 'OSX' : (stristr(PHP_OS, 'WIN') ? 'WIN' : (stristr(PHP_OS, 'LINUX') ? 'LINUX' : PHP_OS))),
            'exception' => null,
        ];

        if ($commandEvent->getException() !== null) {
            $stats['status'] = 'error';
            $stats['exception'] = get_class($commandEvent->getException());
        }

        if ($stats['command_name'] === 'init') {
            $stats['allow_anonymous_stats'] = $GLOBALS['allow_anonymous_stats'] ?? false;
        }

        if (in_array($stats['command_name'], ['worker', 'list', 'help'], true)) {
            return;
        }

        Reporter::report($stats);
    }

    public static function isPharArchive()
    {
        return 'phar:' === substr(__FILE__, 0, 5);
    }
}
