<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Utility;

use PHPUnit\Framework\TestCase;

class ProcessRunnerTest extends TestCase
{
    public function testRun()
    {
        $pop = $this->createMock(ProcessOutputPrinter::class);
        $pr = new ProcessRunner($pop);
        self::assertEquals('true', $pr->run('hostname', 'printf "true"'));
    }
}
