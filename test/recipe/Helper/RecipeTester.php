<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Helper;

use Deployer\Deployer;
use Deployer\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;

class CommonTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ApplicationTester
     */
    private $tester;

    /**
     * @var Deployer
     */
    private $deployer;

    /**
     * @var string
     */
    private $deployPath;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $console = new Application();
        $console->setAutoExit(false);
        $console->setCatchExceptions(false);
        $this->tester = new ApplicationTester($console);

        $this->deployer = new Deployer($console, $input, $output);

        $this->deployPath = __DIR__ . '/local';

        if (is_dir($this->deployPath)) {
            exec("rm -rf $this->deployPath");
        }

        mkdir($this->deployPath);

        require __DIR__ . '/../../recipe/common.php';

        localServer('test')
            ->env('deploy_path', $this->deployPath);

        $this->deployer->addConsoleCommands();
    }

    public static function tearDownAfterClass()
    {
        if (is_dir(__DIR__ . '/local')) {
            exec("rm -rf " . __DIR__ . '/local');
        }
    }

    public function testPrepare()
    {
        $this->tester->run(['command' => 'deploy:prepare']);

        $this->assertFileExists($this->deployPath . '/releases');
        $this->assertFileExists($this->deployPath . '/shared');
    }

    public function testRelease()
    {
        $this->tester->run(['command' => 'deploy:release']);

        $this->assertFileExists($this->deployPath . '/release');
        $this->assertFileExists($deployPath = readlink($this->deployPath . '/release'));
        $this->assertEquals(1, basename($deployPath));
    }
}
