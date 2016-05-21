<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Log;

class LogSenderTest extends \PHPUnit_Framework_TestCase
{
    public function testLogSender()
    {
        $app = new LogSender('name', 'address');

        $this->assertTrue(method_exists($app, 'writeLog'), 'Class does not have method writeLog');

        $this->assertTrue(method_exists($app, 'init'),' Class does not have method writeLog');
    }
}
