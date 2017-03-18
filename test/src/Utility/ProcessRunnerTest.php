<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Utility;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\Output;

class ProcessRunnerTest extends TestCase
{
    public function testRun()
    {
        $output = $this->createMock(Output::class);
        $pr = new ProcessRunner();
        self::assertEquals('true', $pr->run($output, 'hostname', 'printf "true"'));
    }
}
