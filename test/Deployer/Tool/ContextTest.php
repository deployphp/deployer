<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Tool;

use Deployer\Tool\Context;

class ContextTest extends \PHPUnit_Framework_TestCase
{
    public function testContext()
    {
        Context::clear();

        $t1 = $this->getMockBuilder('Deployer\Tool')->disableOriginalConstructor()->getMock();
        Context::push($t1);
        $this->assertInstanceOf('Deployer\Tool', Context::get());

        $t2 = $this->getMockBuilder('Deployer\Tool')->disableOriginalConstructor()->getMock();
        Context::push($t2);
        $this->assertInstanceOf('Deployer\Tool', Context::get());

        Context::pop();
        $this->assertInstanceOf('Deployer\Tool', Context::get());
    }
}
