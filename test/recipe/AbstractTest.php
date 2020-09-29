<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Tester\ApplicationTester;

abstract class AbstractTest extends TestCase
{
    /**
     * @var ApplicationTester
     */
    protected $tester;

    public static function setUpBeforeClass(): void
    {
        // Prepare FS
        self::cleanUp();
        mkdir(__TEMP_DIR__);

        // Init repository
        $repository = __DIR__ . '/repository';

        exec("cd $repository && git init");
        exec("cd $repository && git add .");
        exec("cd $repository && git config user.name 'Anton Medvedev'");
        exec("cd $repository && git config user.email 'anton.medv@example.com'");
        exec("cd $repository && git commit -m 'first commit'");
    }

    public static function tearDownAfterClass(): void
    {
        self::cleanUp();
    }

    protected static function cleanUp()
    {
        if (is_dir(__TEMP_DIR__)) {
            exec('rm -rf ' . __TEMP_DIR__);
        }
    }

    /**
     * @param string $recipe
     * @return Deployer
     */
    protected function init(string $recipe)
    {
        $console = new Application();
        $console->setAutoExit(false);
        $this->tester = new ApplicationTester($console);

        $deployer = new Deployer($console);
        Deployer::load($recipe);
        $deployer->init();
        $deployer->config->set('deploy_path', __TEMP_DIR__ . '/{{hostname}}');

        return $deployer;
    }
}
