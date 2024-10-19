<?php
namespace RingCentral\Tests\Psr7;

use Psr\Http\Message\StreamInterface;
use RingCentral\Psr7;
use RingCentral\Psr7\StreamDecoratorTrait;

class Str extends StreamDecoratorTrait implements StreamInterface
{
}

/**
 * @covers RingCentral\Psr7\StreamDecoratorTrait
 */
class StreamDecoratorTraitTest extends \PHPUnit_Framework_TestCase
{
    private $a;
    private $b;
    private $c;

    public function setUp()
    {
        $this->c = fopen('php://temp', 'r+');
        fwrite($this->c, 'foo');
        fseek($this->c, 0);
        $this->a = Psr7\stream_for($this->c);
        $this->b = new Str($this->a);
    }

    public function testCatchesExceptionsWhenCastingToString()
    {
        $s = $this->getMockBuilder('Psr\Http\Message\StreamInterface')
                  ->setMethods(array('read'))
                  ->getMockForAbstractClass();
        $s->expects($this->once())
          ->method('read')
          ->will($this->throwException(new \Exception('foo')));
        $msg = '';
        set_error_handler(function ($errNo, $str) use (&$msg) {
            $msg = $str;
        });
        echo new Str($s);
        restore_error_handler();
        $this->assertContains('foo', $msg);
    }

    public function testToString()
    {
        $this->assertEquals('foo', (string)$this->b);
    }

    public function testHasSize()
    {
        $this->assertEquals(3, $this->b->getSize());
    }

    public function testReads()
    {
        $this->assertEquals('foo', $this->b->read(10));
    }

    public function testCheckMethods()
    {
        $this->assertEquals($this->a->isReadable(), $this->b->isReadable());
        $this->assertEquals($this->a->isWritable(), $this->b->isWritable());
        $this->assertEquals($this->a->isSeekable(), $this->b->isSeekable());
    }

    public function testSeeksAndTells()
    {
        $this->b->seek(1);
        $this->assertEquals(1, $this->a->tell());
        $this->assertEquals(1, $this->b->tell());
        $this->b->seek(0);
        $this->assertEquals(0, $this->a->tell());
        $this->assertEquals(0, $this->b->tell());
        $this->b->seek(0, SEEK_END);
        $this->assertEquals(3, $this->a->tell());
        $this->assertEquals(3, $this->b->tell());
    }

    public function testGetsContents()
    {
        $this->assertEquals('foo', $this->b->getContents());
        $this->assertEquals('', $this->b->getContents());
        $this->b->seek(1);
        $this->assertEquals('oo', $this->b->getContents(1));
    }

    public function testCloses()
    {
        $this->b->close();
        $this->assertFalse(is_resource($this->c));
    }

    public function testDetaches()
    {
        $this->b->detach();
        $this->assertFalse($this->b->isReadable());
    }

    public function testWrapsMetadata()
    {
        $this->assertSame($this->b->getMetadata(), $this->a->getMetadata());
        $this->assertSame($this->b->getMetadata('uri'), $this->a->getMetadata('uri'));
    }

    public function testWrapsWrites()
    {
        $this->b->seek(0, SEEK_END);
        $this->b->write('foo');
        $this->assertEquals('foofoo', (string)$this->a);
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testThrowsWithInvalidGetter()
    {
        $this->b->foo;
    }

}