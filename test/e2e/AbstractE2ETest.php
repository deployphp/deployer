<?php declare(strict_types=1);
namespace e2e;

use Deployer\Deployer;
use Deployer\Exception\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;

abstract class AbstractE2ETest extends TestCase
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

    protected static function cleanUp(): void
    {
        if (is_dir(__TEMP_DIR__)) {
            exec('rm -rf ' . __TEMP_DIR__);
        }
    }

    /**
     * @param string $recipe path to recipe file
     * @throws Exception
     */
    protected function init(string $recipe): void
    {
        $console = new Application();
        $console->setAutoExit(false);
        $this->tester = new ApplicationTester($console);

        $this->deployer = new Deployer($console);
        $this->deployer->importer->import($recipe);
        $this->deployer->init();
        $this->deployer->config->set('deploy_path', __TEMP_DIR__ . '/{{hostname}}');
    }
}
