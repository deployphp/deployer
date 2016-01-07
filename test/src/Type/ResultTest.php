<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Type;

class ResultTest extends \PHPUnit_Framework_TestCase
{
    public function testGetOutput()
    {
        $result = new Result("str\n");

        $this->assertEquals("str\n", $result->getOutput());
    }

    public function testToString()
    {
        $result = new Result("str\n");

        $this->assertEquals('str', (string)$result);
    }

    public function testToBool()
    {
        $result = new Result("true\n");

        $this->assertTrue($result->toBool());

        $result = new Result("false\n");

        $this->assertFalse($result->toBool());

        $result = new Result("not-true");

        $this->assertFalse($result->toBool());
    }

    public function testArray()
    {
        $result = new Result("1\n2\n3\n");

        $this->assertEquals([1, 2, 3], $result->toArray());
    }
}
