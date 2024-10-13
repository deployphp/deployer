<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Symfony\Component\Console\Output\Output;

class ParallelTest extends AbstractTest
{
    public const RECIPE = __DIR__ . '/recipe/parallel.php';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        putenv('DEPLOYER_LOCAL_WORKER=false'); // Allow to start workers. Don't forget to disable it later.
    }

    public static function tearDownAfterClass(): void
    {
        putenv('DEPLOYER_LOCAL_WORKER=true');
        parent::tearDownAfterClass();
    }

    public function testWorker()
    {
        $this->init(self::RECIPE);
        $this->tester->run([
            'echo',
            '-f' => self::RECIPE,
            'selector' => 'all',
        ], [
            'verbosity' => Output::VERBOSITY_NORMAL,
        ]);
        self::assertEquals(0, $this->tester->getStatusCode(), $this->tester->getDisplay());
    }

    public function testServer()
    {
        $this->init(self::RECIPE);
        $this->tester->setInputs(['prod', 'Black bear']);
        $this->tester->run([
            'ask',
            '-f' => self::RECIPE,
        ], [
            'verbosity' => Output::VERBOSITY_NORMAL,
            'interactive' => true,
        ]);
        $display = $this->tester->getDisplay();
        self::assertEquals(0, $this->tester->getStatusCode(), $display);
        self::assertStringContainsString('[prod] Question: What kind of bear is best?', $display);
        self::assertStringContainsString('[prod] Black bear', $display);
    }

    public function testOption()
    {
        $this->init(self::RECIPE);
        $this->tester->run(
            [
                'echo',
                'selector' => 'all',
                '-o' => ['greet=Hello'],
                '-f' => self::RECIPE,
                //'-l' => 1,
            ],
            [
                'verbosity' => Output::VERBOSITY_DEBUG,
                'interactive' => false,
            ],
        );

        $display = $this->tester->getDisplay();
        self::assertEquals(0, $this->tester->getStatusCode(), $display);
        self::assertStringContainsString('[prod] Hello, prod!', $display);
        self::assertStringContainsString('[beta] Hello, beta!', $display);
    }

    public function testCachedHostConfig()
    {
        $this->init(self::RECIPE);
        $this->tester->run([
            'cache_config_test',
            '-f' => self::RECIPE,
            'selector' => 'all',
        ], [
            'verbosity' => Output::VERBOSITY_NORMAL,
        ]);

        $display = $this->tester->getDisplay();
        self::assertEquals(0, $this->tester->getStatusCode(), $display);
        self::assertTrue(substr_count($display, 'worker on prod') == 1, $display);
        self::assertTrue(substr_count($display, 'worker on beta') == 1, $display);
    }

    public function testHostConfigFromCallback()
    {
        $this->init(self::RECIPE);
        $this->tester->run([
            'host_config_from_callback',
            '-f' => self::RECIPE,
            'selector' => 'all',
        ], [
            'verbosity' => Output::VERBOSITY_NORMAL,
        ]);

        $display = $this->tester->getDisplay();
        self::assertEquals(0, $this->tester->getStatusCode(), $display);
        self::assertTrue(substr_count($display, '[prod] config value is from global') == 1, $display);
        self::assertTrue(substr_count($display, '[beta] config value is from callback') == 1, $display);
    }
}
