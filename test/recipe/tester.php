<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Console\Application;
use Deployer\Task\Context;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Process\Process;

function exec($command)
{
    if (!empty(DepCase::$deployPath)) {
        $command = 'cd ' . DepCase::$deployPath . ' && ' . $command;
    }

    $process = new Process($command);
    $process
        ->mustRun();

    return trim($process->getOutput());
}

abstract class DepCase extends BaseTestCase
{
    /**
     * @var ApplicationTester
     */
    private $tester;

    /**
     * @var Deployer
     */
    protected $deployer;

    /**
     * @var string
     */
    public static $deployPath;

    public static function setUpBeforeClass()
    {
        // Prepare FS
        self::$deployPath = FIXTURES . '/.localhost';
        self::cleanUp();
        mkdir(self::$deployPath);
        self::$deployPath = realpath(self::$deployPath);

        // Init repository
        $repository = FIXTURES . '/repository';
        \exec("cd $repository && git init");
        \exec("cd $repository && git add .");
        \exec("cd $repository && git config user.name 'John Smith'");
        \exec("cd $repository && git config user.email 'john.smith@example.com'");
        \exec("cd $repository && git commit -m 'init commit'");
    }

    public static function tearDownAfterClass()
    {
        self::cleanUp();
    }

    protected static function cleanUp()
    {
        if (is_dir(self::$deployPath)) {
            \exec('rm -rf ' . self::$deployPath);
        }
    }

    public function reset()
    {
        // Create app tester
        $console = new Application();
        $console->setAutoExit(false);
        $console->setCatchExceptions(false);
        $this->tester = new ApplicationTester($console);

        // Prepare Deployer
        $input = $this->createMock(Input::class);
        $output = $this->createMock(Output::class);
        $this->deployer = new Deployer($console, $input, $output);

        // Clear context
        Context::pop();

        // Load recipe
        $this->load();

        // Init Deployer
        $this->deployer->init();
        $this->deployer->getConsole()->afterRun(null);
    }

    /**
     * Load recipe
     */
    abstract protected function load();

    /**
     * Execute command with tester
     *
     * @param string $command
     * @param array $args
     * @param array $options
     * @return string result
     */
    protected function start($command, $args = [], $options = [])
    {
        $this->reset();
        $this->tester->run(['command' => $command] + $args, $options);

        // Clear realpath cache.
        clearstatcache(self::$deployPath);

        return $this->tester->getDisplay();
    }
}
