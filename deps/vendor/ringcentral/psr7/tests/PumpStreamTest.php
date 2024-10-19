<?php
namespace RingCentral\Tests\Psr7;

use RingCentral\Psr7\LimitStream;
use RingCentral\Psr7\PumpStream;
use RingCentral\Psr7;

class PumpStreamTest extends \PHPUnit_Framework_TestCase
{
    public function testHasMetadataAndSize()
    {
        $p = new PumpStream(function () {}, array(
            'metadata' => array('foo' => 'bar'),
            'size'     => 100
        ));

        $this->assertEquals('bar', $p->getMetadata('foo'));
        $this->assertEquals(array('foo' => 'bar'), $p->getMetadata());
        $this->assertEquals(100, $p->getSize());
    }

    public function testCanReadFromCallable()
    {
        $p = Psr7\stream_for(function ($size) {
            return 'a';
        });
        $this->assertEquals('a', $p->read(1));
        $this->assertEquals(1, $p->tell());
        $this->assertEquals('aaaaa', $p->read(5));
        $this->assertEquals(6, $p->tell());
    }

    public function testStoresExcessDataInBuffer()
    {
        $called = array();
        $p = Psr7\stream_for(function ($size) use (&$called) {
            $called[] = $size;
            return 'abcdef';
        });
        $this->assertEquals('a', $p->read(1));
        $this->assertEquals('b', $p->read(1));
        $this->assertEquals('cdef', $p->read(4));
        $this->assertEquals('abcdefabc', $p->read(9));
        $this->assertEquals(array(1, 9, 3), $called);
    }

    public function testInifiniteStreamWrappedInLimitStream()
    {
        $p = Psr7\stream_for(function () { return 'a'; });
        $s = new LimitStream($p, 5);
        $this->assertEquals('aaaaa', (string) $s);
    }

    public function testDescribesCapabilities()
    {
        $p = Psr7\stream_for(function () {});
        $this->assertTrue($p->isReadable());
        $this->assertFalse($p->isSeekable());
        $this->assertFalse($p->isWritable());
        $this->assertNull($p->getSize());
        $this->assertEquals('', $p->getContents());
        $this->assertEquals('', (string) $p);
        $p->close();
        $this->assertEquals('', $p->read(10));
        $this->assertTrue($p->eof());

        try {
            $this->assertFalse($p->write('aa'));
            $this->fail();
        } catch (\RuntimeException $e) {}
    }
}
