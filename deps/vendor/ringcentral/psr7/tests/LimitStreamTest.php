<?php
namespace RingCentral\Tests\Psr7;

use RingCentral\Psr7;
use RingCentral\Psr7\FnStream;
use RingCentral\Psr7\Stream;
use RingCentral\Psr7\LimitStream;
use RingCentral\Psr7\NoSeekStream;

/**
 * @covers RingCentral\Psr7\LimitStream
 */
class LimitStreamTest extends \PHPUnit_Framework_TestCase
{
    /** @var LimitStream */
    protected $body;

    /** @var Stream */
    protected $decorated;

    public function setUp()
    {
        $this->decorated = Psr7\stream_for(fopen(__FILE__, 'r'));
        $this->body = new LimitStream($this->decorated, 10, 3);
    }

    public function testReturnsSubset()
    {
        $body = new LimitStream(Psr7\stream_for('foo'), -1, 1);
        $this->assertEquals('oo', (string) $body);
        $this->assertTrue($body->eof());
        $body->seek(0);
        $this->assertFalse($body->eof());
        $this->assertEquals('oo', $body->read(100));
        $this->assertSame('', $body->read(1));
        $this->assertTrue($body->eof());
    }

    public function testReturnsSubsetWhenCastToString()
    {
        $body = Psr7\stream_for('foo_baz_bar');
        $limited = new LimitStream($body, 3, 4);
        $this->assertEquals('baz', (string) $limited);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unable to seek to stream position 10 with whence 0
     */
    public function testEnsuresPositionCanBeekSeekedTo()
    {
        new LimitStream(Psr7\stream_for(''), 0, 10);
    }

    public function testReturnsSubsetOfEmptyBodyWhenCastToString()
    {
        $body = Psr7\stream_for('01234567891234');
        $limited = new LimitStream($body, 0, 10);
        $this->assertEquals('', (string) $limited);
    }

    public function testReturnsSpecificSubsetOBodyWhenCastToString()
    {
        $body = Psr7\stream_for('0123456789abcdef');
        $limited = new LimitStream($body, 3, 10);
        $this->assertEquals('abc', (string) $limited);
    }

    public function testSeeksWhenConstructed()
    {
        $this->assertEquals(0, $this->body->tell());
        $this->assertEquals(3, $this->decorated->tell());
    }

    public function testAllowsBoundedSeek()
    {
        $this->body->seek(100);
        $this->assertEquals(10, $this->body->tell());
        $this->assertEquals(13, $this->decorated->tell());
        $this->body->seek(0);
        $this->assertEquals(0, $this->body->tell());
        $this->assertEquals(3, $this->decorated->tell());
        try {
            $this->body->seek(-10);
            $this->fail();
        } catch (\RuntimeException $e) {}
        $this->assertEquals(0, $this->body->tell());
        $this->assertEquals(3, $this->decorated->tell());
        $this->body->seek(5);
        $this->assertEquals(5, $this->body->tell());
        $this->assertEquals(8, $this->decorated->tell());
        // Fail
        try {
            $this->body->seek(1000, SEEK_END);
            $this->fail();
        } catch (\RuntimeException $e) {}
    }

    public function testReadsOnlySubsetOfData()
    {
        $data = $this->body->read(100);
        $this->assertEquals(10, strlen($data));
        $this->assertSame('', $this->body->read(1000));

        $this->body->setOffset(10);
        $newData = $this->body->read(100);
        $this->assertEquals(10, strlen($newData));
        $this->assertNotSame($data, $newData);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Could not seek to stream offset 2
     */
    public function testThrowsWhenCurrentGreaterThanOffsetSeek()
    {
        $a = Psr7\stream_for('foo_bar');
        $b = new NoSeekStream($a);
        $c = new LimitStream($b);
        $a->getContents();
        $c->setOffset(2);
    }

    public function testCanGetContentsWithoutSeeking()
    {
        $a = Psr7\stream_for('foo_bar');
        $b = new NoSeekStream($a);
        $c = new LimitStream($b);
        $this->assertEquals('foo_bar', $c->getContents());
    }

    public function testClaimsConsumedWhenReadLimitIsReached()
    {
        $this->assertFalse($this->body->eof());
        $this->body->read(1000);
        $this->assertTrue($this->body->eof());
    }

    public function testContentLengthIsBounded()
    {
        $this->assertEquals(10, $this->body->getSize());
    }

    public function testGetContentsIsBasedOnSubset()
    {
        $body = new LimitStream(Psr7\stream_for('foobazbar'), 3, 3);
        $this->assertEquals('baz', $body->getContents());
    }

    public function testReturnsNullIfSizeCannotBeDetermined()
    {
        $a = new FnStream(array(
            'getSize' => function () { return null; },
            'tell'    => function () { return 0; },
        ));
        $b = new LimitStream($a);
        $this->assertNull($b->getSize());
    }

    public function testLengthLessOffsetWhenNoLimitSize()
    {
        $a = Psr7\stream_for('foo_bar');
        $b = new LimitStream($a, -1, 4);
        $this->assertEquals(3, $b->getSize());
    }
}
