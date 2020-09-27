<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Symfony\Component\Console\Output\Output;

class WorkerTest extends AbstractTest
{
    public function testWorker()
    {
        $recipe = __DIR__ . '/deploy.php';
        $deployer = $this->init($recipe);

        $this->tester->run(['worker',
            'worker-task' => 'deploy:success',
            'worker-host' => 'prod',
            'config-directory' => sys_get_temp_dir(),
            'master-port' => '1234',
            'original-task' => 'deploy',
            '-s' => 'all',
            '-f' => $recipe,
            '-l' => 1], [
            'verbosity' => Output::VERBOSITY_VERBOSE,
            'interactive' => false,
        ]);

        $display = $this->tester->getDisplay();
        self::assertEquals(0, $this->tester->getStatusCode(), $display);
        self::assertStringContainsString('info successfully deployed!', $display);
    }
}
