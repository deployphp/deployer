<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Log;

class LogWriterTest extends \PHPUnit_Framework_TestCase
{
    public function testLogWriter()
    {
        $app = new LogWriter('name', 'path');

        $this->assertTrue(method_exists($app, 'writeLog'),'Class does not have method writeLog');
    }
}
