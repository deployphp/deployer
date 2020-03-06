<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Collection\Collection;
use Deployer\Console\Application;
use Deployer\Console\AutocompleteCommand;
use Deployer\Console\CommandEvent;
use Deployer\Console\DebugCommand;
use Deployer\Console\InitCommand;
use Deployer\Console\Output\Informer;
use Deployer\Console\Output\OutputWatcher;
use Deployer\Console\RunCommand;
use Deployer\Console\SshCommand;
use Deployer\Console\TaskCommand;
use Deployer\Console\WorkerCommand;
use Deployer\Executor\ParallelExecutor;
use Deployer\Executor\SeriesExecutor;
use Deployer\Logger\Handler\FileHandler;
use Deployer\Logger\Handler\NullHandler;
use Deployer\Logger\Logger;
use function Deployer\Support\array_merge_alternate;
use Deployer\Task;
use Deployer\Utility\ProcessOutputPrinter;
use Deployer\Utility\ProcessRunner;
use Deployer\Utility\Reporter;
use Deployer\Utility\Rsync;
use Pimple\Container;
use Symfony\Component\Console;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Deployer class represents DI container for configuring
 *
 * @property Application $console
 * @property Task\TaskCollection|Task\Task[] $tasks
 * @property Host\HostCollection|Collection|Host\Host[] $hosts
 * @property Collection $config
 * @property Rsync $rsync
 * @property Ssh\Client $sshClient
 * @property ProcessRunner $processRunner
 * @property Task\ScriptManager $scriptManager
 * @property Host\HostSelector $hostSelector
 * @property SeriesExecutor $seriesExecutor
 * @property ParallelExecutor $parallelExecutor
 * @property Informer $informer
 * @property Logger $logger
 * @property ProcessOutputPrinter $pop
 * @property Collection $fail
 */
class Deployer extends Container
{
    /**
     * Global instance of deployer. It's can be accessed only after constructor call.
     * @var Deployer
     */
    private static $instance;

    /**
     * @param Application $console
     */
    public function __construct(Application $console)
    {
        parent::__construct();

        /******************************
         *           Console          *
         ******************************/

        $this['console'] = function () use ($console) {
            $console->catchIO(function ($input, $output) {
                $this['input'] = $input;
                $this['output'] =  new OutputWatcher($output);
                return [$this['input'], $this['output']];
            });
            return $console;
        };

        /******************************
         *           Config           *
         ******************************/

        $this['config'] = function () {
            return new Collection();
        };
        $this->config['ssh_multiplexing'] = true;
        $this->config['default_stage'] = null;

        /******************************
         *            Core            *
         ******************************/

        $this['pop'] = function ($c) {
            return new ProcessOutputPrinter($c['output'], $c['logger']);
        };
        $this['sshClient'] = function ($c) {
            return new Ssh\Client($c['output'], $c['pop'], $c['config']['ssh_multiplexing']);
        };
        $this['rsync'] = function ($c) {
            return new Rsync($c['pop']);
        };
        $this['processRunner'] = function ($c) {
            return new ProcessRunner($c['pop']);
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
        $this['hostSelector'] = function ($c) {
            $defaultStage = $c['config']['default_stage'];
            if (is_object($defaultStage) && ($defaultStage instanceof \Closure)) {
                $defaultStage = call_user_func($defaultStage);
            }
            return new Host\HostSelector($c['hosts'], $defaultStage);
        };
        $this['fail'] = function () {
            return new Collection();
        };
        $this['informer'] = function ($c) {
            return new Informer($c['output']);
        };
        $this['seriesExecutor'] = function ($c) {
            return new SeriesExecutor($c['input'], $c['output'], $c['informer']);
        };
        $this['parallelExecutor'] = function ($c) {
            return new ParallelExecutor($c['input'], $c['output'], $c['informer'], $c['console']);
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

        /******************************
         *        Init command        *
         ******************************/

        $this['init_command'] = function () {
            return new InitCommand();
        };

        self::$instance = $this;
    }

    /**
     * @return Deployer
     */
    public static function get()
    {
        return self::$instance;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public static function setDefault($name, $value)
    {
        Deployer::get()->config[$name] = $value;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public static function getDefault($name, $default = null)
    {
        return self::hasDefault($name) ? Deployer::get()->config[$name] : $default;
    }

    /**
     * @param string $name
     * @return boolean
     */
    public static function hasDefault($name)
    {
        return isset(Deployer::get()->config[$name]);
    }

    /**
     * @param string $name
     * @param array $array
     */
    public static function addDefault($name, $array)
    {
        if (self::hasDefault($name)) {
            $config = self::getDefault($name);
            if (!is_array($config)) {
                throw new \RuntimeException("Configuration parameter `$name` isn't array.");
            }
            self::setDefault($name, array_merge_alternate($config, $array));
        } else {
            self::setDefault($name, $array);
        }
    }

    /**
     * Init console application
     */
    public function init()
    {
        $this->addConsoleCommands();
        $this->getConsole()->add(new WorkerCommand($this));
        $this->getConsole()->add($this['init_command']);
        $this->getConsole()->add(new SshCommand($this));
        $this->getConsole()->add(new RunCommand($this));
        $this->getConsole()->add(new DebugCommand($this));
        $this->getConsole()->add(new AutocompleteCommand());
        $this->getConsole()->afterRun([$this, 'collectAnonymousStats']);
    }

    /**
     * Transform tasks to console commands.
     */
    public function addConsoleCommands()
    {
        $this->getConsole()->addUserArgumentsAndOptions();

        foreach ($this->tasks as $name => $task) {
            if ($task->isPrivate()) {
                continue;
            }

            $this->getConsole()->add(new TaskCommand($name, $task->getDescription(), $this));
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
     * @return Application
     */
    public function getConsole()
    {
        return $this['console'];
    }

    /**
     * @return Console\Input\InputInterface
     */
    public function getInput()
    {
        return $this['input'];
    }

    /**
     * @return Console\Output\OutputInterface
     */
    public function getOutput()
    {
        return $this['output'];
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
        // Init Deployer
        $console = new Application('Deployer', $version);
        $input = new ArgvInput();
        $output = new ConsoleOutput();
        $deployer = new self($console);

        // Pretty-print uncaught exceptions in symfony-console
        set_exception_handler(function ($e) use ($input, $output, $deployer) {
            $io = new SymfonyStyle($input, $output);
            $io->block($e->getMessage(), get_class($e), 'fg=white;bg=red', ' ', true);
            $io->block($e->getTraceAsString());

            $deployer->logger->log('['. get_class($e) .'] '. $e->getMessage());
            $deployer->logger->log($e->getTraceAsString());
            exit(1);
        });

        // Require deploy.php file
        self::loadRecipe($deployFile);

        // Run Deployer
        $deployer->init();
        $console->run($input, $output);
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
            'deployer_phar' => $this->getConsole()->isPharArchive(),
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

    /**
     * Load recipe file
     *
     * @param string $deployFile
     *
     * @return void
     * @codeCoverageIgnore
     */
    public static function loadRecipe($deployFile)
    {
        if (is_readable($deployFile)) {
            // Prevent variable leak into deploy.php file
            call_user_func(function () use ($deployFile) {
                // reorder autoload stack.
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

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public static function getCalledScript(): string
    {
        $executable = !empty($_SERVER['_']) ? $_SERVER['_'] : $_SERVER['PHP_SELF'];
        $shortcut = false !== strpos(getenv('PATH'), dirname($executable)) ? basename($executable) : $executable;

        if ($executable !== $_SERVER['PHP_SELF']) {
            return sprintf('%s %s', $shortcut, $_SERVER['PHP_SELF']);
        }

        return $shortcut;
    }
}
