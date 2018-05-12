<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Support;

use PHPUnit\Framework\TestCase;

class UnixTest extends TestCase
{
    public function testParseHomeDir()
    {
        $this->assertStringStartsWith('/', Unix::parseHomeDir('~/path'));
    }
}
