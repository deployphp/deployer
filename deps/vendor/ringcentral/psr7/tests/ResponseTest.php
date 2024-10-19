<?php
namespace RingCentral\Tests\Psr7;

use RingCentral\Psr7\Response;
use RingCentral\Psr7;

/**
 * @covers RingCentral\Psr7\MessageTrait
 * @covers RingCentral\Psr7\Response
 */
class ResponseTest extends \PHPUnit_Framework_TestCase
{
    public function testAddsDefaultReason()
    {
        $r = new Response('200');
        $this->assertSame(200, $r->getStatusCode());
        $this->assertEquals('OK', $r->getReasonPhrase());
    }

    public function testCanGiveCustomReason()
    {
        $r = new Response(200, array(), null, '1.1', 'bar');
        $this->assertEquals('bar', $r->getReasonPhrase());
    }

    public function testCanGiveCustomProtocolVersion()
    {
        $r = new Response(200, array(), null, '1000');
        $this->assertEquals('1000', $r->getProtocolVersion());
    }

    public function testCanCreateNewResponseWithStatusAndNoReason()
    {
        $r = new Response(200);
        $r2 = $r->withStatus(201);
        $this->assertEquals(200, $r->getStatusCode());
        $this->assertEquals('OK', $r->getReasonPhrase());
        $this->assertEquals(201, $r2->getStatusCode());
        $this->assertEquals('Created', $r2->getReasonPhrase());
    }

    public function testCanCreateNewResponseWithStatusAndReason()
    {
        $r = new Response(200);
        $r2 = $r->withStatus(201, 'Foo');
        $this->assertEquals(200, $r->getStatusCode());
        $this->assertEquals('OK', $r->getReasonPhrase());
        $this->assertEquals(201, $r2->getStatusCode());
        $this->assertEquals('Foo', $r2->getReasonPhrase());
    }

    public function testCreatesResponseWithAddedHeaderArray()
    {
        $r = new Response();
        $r2 = $r->withAddedHeader('foo', array('baz', 'bar'));
        $this->assertFalse($r->hasHeader('foo'));
        $this->assertEquals('baz, bar', $r2->getHeaderLine('foo'));
    }

    public function testReturnsIdentityWhenRemovingMissingHeader()
    {
        $r = new Response();
        $this->assertSame($r, $r->withoutHeader('foo'));
    }

    public function testAlwaysReturnsBody()
    {
        $r = new Response();
        $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $r->getBody());
    }

    public function testCanSetHeaderAsArray()
    {
        $r = new Response(200, array(
            'foo' => array('baz ', ' bar ')
        ));
        $this->assertEquals('baz, bar', $r->getHeaderLine('foo'));
        $this->assertEquals(array('baz', 'bar'), $r->getHeader('foo'));
    }

    public function testSameInstanceWhenSameBody()
    {
        $r = new Response(200, array(), 'foo');
        $b = $r->getBody();
        $this->assertSame($r, $r->withBody($b));
    }

    public function testNewInstanceWhenNewBody()
    {
        $r = new Response(200, array(), 'foo');
        $b2 = Psr7\stream_for('abc');
        $this->assertNotSame($r, $r->withBody($b2));
    }

    public function testSameInstanceWhenSameProtocol()
    {
        $r = new Response(200);
        $this->assertSame($r, $r->withProtocolVersion('1.1'));
    }

    public function testNewInstanceWhenNewProtocol()
    {
        $r = new Response(200);
        $this->assertNotSame($r, $r->withProtocolVersion('1.0'));
    }

    public function testNewInstanceWhenRemovingHeader()
    {
        $r = new Response(200, array('Foo' => 'Bar'));
        $r2 = $r->withoutHeader('Foo');
        $this->assertNotSame($r, $r2);
        $this->assertFalse($r2->hasHeader('foo'));
    }

    public function testNewInstanceWhenAddingHeader()
    {
        $r = new Response(200, array('Foo' => 'Bar'));
        $r2 = $r->withAddedHeader('Foo', 'Baz');
        $this->assertNotSame($r, $r2);
        $this->assertEquals('Bar, Baz', $r2->getHeaderLine('foo'));
    }

    public function testNewInstanceWhenAddingHeaderArray()
    {
        $r = new Response(200, array('Foo' => 'Bar'));
        $r2 = $r->withAddedHeader('Foo', array('Baz', 'Qux'));
        $this->assertNotSame($r, $r2);
        $this->assertEquals(array('Bar', 'Baz', 'Qux'), $r2->getHeader('foo'));
    }

    public function testNewInstanceWhenAddingHeaderThatWasNotThereBefore()
    {
        $r = new Response(200, array('Foo' => 'Bar'));
        $r2 = $r->withAddedHeader('Baz', 'Bam');
        $this->assertNotSame($r, $r2);
        $this->assertEquals('Bam', $r2->getHeaderLine('Baz'));
        $this->assertEquals('Bar', $r2->getHeaderLine('Foo'));
    }

    public function testRemovesPreviouslyAddedHeaderOfDifferentCase()
    {
        $r = new Response(200, array('Foo' => 'Bar'));
        $r2 = $r->withHeader('foo', 'Bam');
        $this->assertNotSame($r, $r2);
        $this->assertEquals('Bam', $r2->getHeaderLine('Foo'));
    }

    public function testBodyConsistent()
    {
        $r = new Response(200, array(), '0');
        $this->assertEquals('0', (string)$r->getBody());
    }

}
