<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Helper;

use Deployer\Console\Application;
use Deployer\Deployer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\ApplicationTester;

abstract class RecipeTester extends TestCase
{
    use RecipeAssertionsTrait;

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
    protected static $deployPath;

    public static function setUpBeforeClass()
    {
        // Prepare FS
        self::$deployPath = __DIR__ . '/../../localhost';
        self::cleanUp();
        mkdir(self::$deployPath);
        self::$deployPath = realpath(self::$deployPath);
    }

    public function setUp()
    {
        // Create App tester.
        $console = new Application();
        $console->setAutoExit(false);
        $console->setCatchExceptions(false);
        $this->tester = new ApplicationTester($console);

        // Prepare Deployer
        $input = $this->createMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->createMock('Symfony\Component\Console\Output\OutputInterface');
        $this->deployer = new Deployer($console, $input, $output);

        // Load recipe
        $this->setUpServer();
        $this->loadRecipe();

        // Init Deployer
        $this->deployer->addConsoleCommands();
    }

    protected function setUpServer()
    {
        \Deployer\localServer('localhost')
            ->set('deploy_path', self::$deployPath);
    }


    public static function tearDownAfterClass()
    {
        self::cleanUp();
    }

    /**
     *  Remove deploy directory from file system.
     */
    protected static function cleanUp()
    {
        if (is_dir(self::$deployPath)) {
            exec('rm -rf ' . self::$deployPath);
        }
    }

    /**
     * Execute command with tester.
     *
     * @param string $command
     * @param array $args
     * @param array $options
     * @return string Display result.
     */
    protected function exec($command, $args = [], $options = [])
    {
        $this->tester->run(['command' => $command] + $args, $options);

        // Clear realpath cache.
        clearstatcache(self::$deployPath);

        return $this->tester->getDisplay();
    }

    /**
     * Load or describe recipe.
     *
     * @return void
     */
    abstract protected function loadRecipe();

    /**
     * @param string $name
     * @param string $server
     * @return string
     */
    protected function getEnv($name, $server = 'localhost')
    {
        return $this->deployer->environments->get($server)->get($name);
    }

    /**
     * @return \Deployer\Task\Task[]|\Deployer\Task\TaskCollection
     */
    protected function getTasks()
    {
        return $this->deployer->tasks;
    }
}
