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

/**
 * @deprecated Use JoyTest instead.
 */
abstract class AbstractTest extends TestCase
{
    /**
     * @var ApplicationTester
     */
    protected $tester;

    /**
     * @var Deployer
     */
    protected $deployer;

    public static function setUpBeforeClass(): void
    {
        self::cleanUp();
        mkdir(__TEMP_DIR__);
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

    protected function init(string $recipe)
    {
        $console = new Application();
        $console->setAutoExit(false);
        $this->tester = new ApplicationTester($console);

        $this->deployer = new Deployer($console);
        $this->deployer->importer->import($recipe);
        $this->deployer->init();
        $this->deployer->config->set('deploy_path', __TEMP_DIR__ . '/{{hostname}}');
    }

    protected function dep(string $recipe, string $task)
    {
        $this->init($recipe);
        $this->tester->run([
            $task,
            'selector' => 'all',
            '-f' => $recipe,
            '-l' => 1,
        ], [
            'verbosity' => Output::VERBOSITY_VERBOSE,
            'interactive' => false,
        ]);
    }
}
