<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Component\ProcessRunner;

use PHPUnit\Framework\TestCase;
use function Deployer\localhost;

class PrinterTest extends TestCase
{
    public function testRun()
    {
        $pop = $this->createMock(Printer::class);
        $pr = new ProcessRunner($pop);
        self::assertEquals('true', $pr->run(localhost(), 'printf "true"'));
    }
}
