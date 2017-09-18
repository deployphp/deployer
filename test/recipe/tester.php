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
    if (!empty(DepCase::$currentPath)) {
        $command = 'cd ' . DepCase::$currentPath . ' && ' . $command;
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
    public static $tmpPath;

    /**
     * @var string
     */
    public static $currentPath = '';

    public static function setUpBeforeClass()
    {

        // Prepare FS
        self::$tmpPath = DEPLOYER_FIXTURES . '/recipe/tmp';
        self::cleanUp();
        mkdir(self::$tmpPath);
        self::$tmpPath = realpath(self::$tmpPath);

        // Init repository
        $repository = DEPLOYER_FIXTURES . '/repository';
        if (is_dir($repository)) {
            \exec('rm -rf ' . $repository);
        }
        \exec("mkdir -p $repository");
        \exec("cd $repository && git init");
        \exec("cd $repository && git config user.name 'John Smith'");
        \exec("cd $repository && git config user.email 'john.smith@example.com'");
        file_put_contents("$repository/composer.json", json_encode(['require' => ['']]));
        \exec("cd $repository && git add .");
        \exec("cd $repository && git commit -m 'init commit'");

        // Submodules
        $modules = ['module1', 'module2'];
        foreach ($modules as $module) {
            \exec("cd $repository && " .
                "mkdir -p $module && " .
                "touch $module/master && " .
                "cd $module && " .
                "git init && " .
                "git config user.name 'John Smith' && " .
                "git config user.email 'john.smith@example.com' && " .
                "git add . && " .
                "git commit -m 'init $module commit'");
            \exec("cd $repository && git submodule add --name $module ./$module");
        }
        \exec("cd $repository && git commit -a -m 'adding modules'");

        // Branches
        $branches = ['branch1'];
        foreach ($branches as $branch) {
            $module = $modules[0];
            \exec("cd $repository && git checkout -b $branch 2>/dev/null");
            \exec("cd $repository/$module && git checkout -b $branch 2> /dev/null && touch $branch && git add $branch && git commit -a -m 'adding branch $branch'");
            \exec("cd $repository && touch $branch && git add $branch $module && git commit -a -m 'adding branch $branch'");

            //Other module stays at master
            $module = $modules[1];
            \exec("cd $repository/$module && git checkout -b $branch 2> /dev/null && touch $branch && git add $branch && git commit -a -m 'adding branch $branch' && git checkout master 2> /dev/null");
        }

        // Move back to master
        \exec("cd $repository && git checkout master 2> /dev/null && git submodule update");
    }

    public static function tearDownAfterClass()
    {
        self::cleanUp();
    }

    protected static function cleanUp()
    {
        if (is_dir(self::$tmpPath)) {
            \exec('rm -rf ' . self::$tmpPath);
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
        clearstatcache(self::$tmpPath);

        return $this->tester->getDisplay();
    }
}
