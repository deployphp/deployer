<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Deployer\Deployer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\ApplicationTester;

abstract class JoyTest extends TestCase
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

    protected function dep(string $task, array $args = []): int
    {
        $recipe = __TEMP_DIR__ . '/' . get_called_class() . '.php';
        file_put_contents($recipe, $this->recipe());
        $this->init($recipe);
        return $this->tester->run(array_merge([
            $task,
            'selector' => 'all',
            '--file' => $recipe,
            '--limit' => 1
        ], $args), [
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE,
            'interactive' => false,
        ]);
    }

    abstract protected function recipe(): string;
}
