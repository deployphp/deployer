<?php
namespace RingCentral\Tests\Psr7;

use RingCentral\Psr7\AppendStream;
use RingCentral\Psr7;

class AppendStreamTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Each stream must be readable
     */
    public function testValidatesStreamsAreReadable()
    {
        $a = new AppendStream();
        $s = $this->getMockBuilder('Psr\Http\Message\StreamInterface')
            ->setMethods(array('isReadable'))
            ->getMockForAbstractClass();
        $s->expects($this->once())
            ->method('isReadable')
            ->will($this->returnValue(false));
        $a->addStream($s);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The AppendStream can only seek with SEEK_SET
     */
    public function testValidatesSeekType()
    {
        $a = new AppendStream();
        $a->seek(100, SEEK_CUR);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unable to seek stream 0 of the AppendStream
     */
    public function testTriesToRewindOnSeek()
    {
        $a = new AppendStream();
        $s = $this->getMockBuilder('Psr\Http\Message\StreamInterface')
            ->setMethods(array('isReadable', 'rewind', 'isSeekable'))
            ->getMockForAbstractClass();
        $s->expects($this->once())
            ->method('isReadable')
            ->will($this->returnValue(true));
        $s->expects($this->once())
            ->method('isSeekable')
            ->will($this->returnValue(true));
        $s->expects($this->once())
            ->method('rewind')
            ->will($this->throwException(new \RuntimeException()));
        $a->addStream($s);
        $a->seek(10);
    }

    public function testSeeksToPositionByReading()
    {
        $a = new AppendStream(array(
            Psr7\stream_for('foo'),
            Psr7\stream_for('bar'),
            Psr7\stream_for('baz'),
        ));

        $a->seek(3);
        $this->assertEquals(3, $a->tell());
        $this->assertEquals('bar', $a->read(3));

        $a->seek(6);
        $this->assertEquals(6, $a->tell());
        $this->assertEquals('baz', $a->read(3));
    }

    public function testDetachesEachStream()
    {
        $s1 = Psr7\stream_for('foo');
        $s2 = Psr7\stream_for('bar');
        $a = new AppendStream(array($s1, $s2));
        $this->assertSame('foobar', (string) $a);
        $a->detach();
        $this->assertSame('', (string) $a);
        $this->assertSame(0, $a->getSize());
    }

    public function testClosesEachStream()
    {
        $s1 = Psr7\stream_for('foo');
        $a = new AppendStream(array($s1));
        $a->close();
        $this->assertSame('', (string) $a);
    }

    /**
     * @expectedExceptionMessage Cannot write to an AppendStream
     * @expectedException \RuntimeException
     */
    public function testIsNotWritable()
    {
        $a = new AppendStream(array(Psr7\stream_for('foo')));
        $this->assertFalse($a->isWritable());
        $this->assertTrue($a->isSeekable());
        $this->assertTrue($a->isReadable());
        $a->write('foo');
    }

    public function testDoesNotNeedStreams()
    {
        $a = new AppendStream();
        $this->assertEquals('', (string) $a);
    }

    public function testCanReadFromMultipleStreams()
    {
        $a = new AppendStream(array(
            Psr7\stream_for('foo'),
            Psr7\stream_for('bar'),
            Psr7\stream_for('baz'),
        ));
        $this->assertFalse($a->eof());
        $this->assertSame(0, $a->tell());
        $this->assertEquals('foo', $a->read(3));
        $this->assertEquals('bar', $a->read(3));
        $this->assertEquals('baz', $a->read(3));
        $this->assertSame('', $a->read(1));
        $this->assertTrue($a->eof());
        $this->assertSame(9, $a->tell());
        $this->assertEquals('foobarbaz', (string) $a);
    }

    public function testCanDetermineSizeFromMultipleStreams()
    {
        $a = new AppendStream(array(
            Psr7\stream_for('foo'),
            Psr7\stream_for('bar')
        ));
        $this->assertEquals(6, $a->getSize());

        $s = $this->getMockBuilder('Psr\Http\Message\StreamInterface')
            ->setMethods(array('isSeekable', 'isReadable'))
            ->getMockForAbstractClass();
        $s->expects($this->once())
            ->method('isSeekable')
            ->will($this->returnValue(null));
        $s->expects($this->once())
            ->method('isReadable')
            ->will($this->returnValue(true));
        $a->addStream($s);
        $this->assertNull($a->getSize());
    }

    public function testCatchesExceptionsWhenCastingToString()
    {
        $s = $this->getMockBuilder('Psr\Http\Message\StreamInterface')
            ->setMethods(array('isSeekable', 'read', 'isReadable', 'eof'))
            ->getMockForAbstractClass();
        $s->expects($this->once())
            ->method('isSeekable')
            ->will($this->returnValue(true));
        $s->expects($this->once())
            ->method('read')
            ->will($this->throwException(new \RuntimeException('foo')));
        $s->expects($this->once())
            ->method('isReadable')
            ->will($this->returnValue(true));
        $s->expects($this->any())
            ->method('eof')
            ->will($this->returnValue(false));
        $a = new AppendStream(array($s));
        $this->assertFalse($a->eof());
        $this->assertSame('', (string) $a);
    }

    public function testCanDetach()
    {
        $s = new AppendStream();
        $s->detach();
    }

    public function testReturnsEmptyMetadata()
    {
        $s = new AppendStream();
        $this->assertEquals(array(), $s->getMetadata());
        $this->assertNull($s->getMetadata('foo'));
    }
}
