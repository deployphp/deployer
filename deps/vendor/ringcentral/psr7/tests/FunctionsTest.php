<?php
namespace RingCentral\Tests\Psr7;

use RingCentral\Psr7;
use RingCentral\Psr7\FnStream;
use RingCentral\Psr7\NoSeekStream;

class FunctionsTest extends \PHPUnit_Framework_TestCase
{
    public function testCopiesToString()
    {
        $s = Psr7\stream_for('foobaz');
        $this->assertEquals('foobaz', Psr7\copy_to_string($s));
        $s->seek(0);
        $this->assertEquals('foo', Psr7\copy_to_string($s, 3));
        $this->assertEquals('baz', Psr7\copy_to_string($s, 3));
        $this->assertEquals('', Psr7\copy_to_string($s));
    }

    public function testCopiesToStringStopsWhenReadFails()
    {
        $s1 = Psr7\stream_for('foobaz');
        $s1 = FnStream::decorate($s1, array(
            'read' => function () { return ''; }
        ));
        $result = Psr7\copy_to_string($s1);
        $this->assertEquals('', $result);
    }

    public function testCopiesToStream()
    {
        $s1 = Psr7\stream_for('foobaz');
        $s2 = Psr7\stream_for('');
        Psr7\copy_to_stream($s1, $s2);
        $this->assertEquals('foobaz', (string) $s2);
        $s2 = Psr7\stream_for('');
        $s1->seek(0);
        Psr7\copy_to_stream($s1, $s2, 3);
        $this->assertEquals('foo', (string) $s2);
        Psr7\copy_to_stream($s1, $s2, 3);
        $this->assertEquals('foobaz', (string) $s2);
    }

    public function testStopsCopyToStreamWhenWriteFails()
    {
        $s1 = Psr7\stream_for('foobaz');
        $s2 = Psr7\stream_for('');
        $s2 = FnStream::decorate($s2, array('write' => function () { return 0; }));
        Psr7\copy_to_stream($s1, $s2);
        $this->assertEquals('', (string) $s2);
    }

    public function testStopsCopyToSteamWhenWriteFailsWithMaxLen()
    {
        $s1 = Psr7\stream_for('foobaz');
        $s2 = Psr7\stream_for('');
        $s2 = FnStream::decorate($s2, array('write' => function () { return 0; }));
        Psr7\copy_to_stream($s1, $s2, 10);
        $this->assertEquals('', (string) $s2);
    }

    public function testStopsCopyToSteamWhenReadFailsWithMaxLen()
    {
        $s1 = Psr7\stream_for('foobaz');
        $s1 = FnStream::decorate($s1, array('read' => function () { return ''; }));
        $s2 = Psr7\stream_for('');
        Psr7\copy_to_stream($s1, $s2, 10);
        $this->assertEquals('', (string) $s2);
    }

    public function testReadsLines()
    {
        $s = Psr7\stream_for("foo\nbaz\nbar");
        $this->assertEquals("foo\n", Psr7\readline($s));
        $this->assertEquals("baz\n", Psr7\readline($s));
        $this->assertEquals("bar", Psr7\readline($s));
    }

    public function testReadsLinesUpToMaxLength()
    {
        $s = Psr7\stream_for("12345\n");
        $this->assertEquals("123", Psr7\readline($s, 4));
        $this->assertEquals("45\n", Psr7\readline($s));
    }

    public function testReadsLineUntilFalseReturnedFromRead()
    {
        $s = $this->getMockBuilder('RingCentral\Psr7\Stream')
            ->setMethods(array('read', 'eof'))
            ->disableOriginalConstructor()
            ->getMock();
        $s->expects($this->exactly(2))
            ->method('read')
            ->will($this->returnCallback(function () {
                static $c = false;
                if ($c) {
                    return false;
                }
                $c = true;
                return 'h';
            }));
        $s->expects($this->exactly(2))
            ->method('eof')
            ->will($this->returnValue(false));
        $this->assertEquals("h", Psr7\readline($s));
    }

    public function testCalculatesHash()
    {
        $s = Psr7\stream_for('foobazbar');
        $this->assertEquals(md5('foobazbar'), Psr7\hash($s, 'md5'));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testCalculatesHashThrowsWhenSeekFails()
    {
        $s = new NoSeekStream(Psr7\stream_for('foobazbar'));
        $s->read(2);
        Psr7\hash($s, 'md5');
    }

    public function testCalculatesHashSeeksToOriginalPosition()
    {
        $s = Psr7\stream_for('foobazbar');
        $s->seek(4);
        $this->assertEquals(md5('foobazbar'), Psr7\hash($s, 'md5'));
        $this->assertEquals(4, $s->tell());
    }

    public function testOpensFilesSuccessfully()
    {
        $r = Psr7\try_fopen(__FILE__, 'r');
        $this->assertInternalType('resource', $r);
        fclose($r);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unable to open /path/to/does/not/exist using mode r
     */
    public function testThrowsExceptionNotWarning()
    {
        Psr7\try_fopen('/path/to/does/not/exist', 'r');
    }

    public function parseQueryProvider()
    {
        return array(
            // Does not need to parse when the string is empty
            array('', array()),
            // Can parse mult-values items
            array('q=a&q=b', array('q' => array('a', 'b'))),
            // Can parse multi-valued items that use numeric indices
            array('q[0]=a&q[1]=b', array('q[0]' => 'a', 'q[1]' => 'b')),
            // Can parse duplicates and does not include numeric indices
            array('q[]=a&q[]=b', array('q[]' => array('a', 'b'))),
            // Ensures that the value of "q" is an array even though one value
            array('q[]=a', array('q[]' => 'a')),
            // Does not modify "." to "_" like PHP's parse_str()
            array('q.a=a&q.b=b', array('q.a' => 'a', 'q.b' => 'b')),
            // Can decode %20 to " "
            array('q%20a=a%20b', array('q a' => 'a b')),
            // Can parse funky strings with no values by assigning each to null
            array('q&a', array('q' => null, 'a' => null)),
            // Does not strip trailing equal signs
            array('data=abc=', array('data' => 'abc=')),
            // Can store duplicates without affecting other values
            array('foo=a&foo=b&?µ=c', array('foo' => array('a', 'b'), '?µ' => 'c')),
            // Sets value to null when no "=" is present
            array('foo', array('foo' => null)),
            // Preserves "0" keys.
            array('0', array('0' => null)),
            // Sets the value to an empty string when "=" is present
            array('0=', array('0' => '')),
            // Preserves falsey keys
            array('var=0', array('var' => '0')),
            array('a[b][c]=1&a[b][c]=2', array('a[b][c]' => array('1', '2'))),
            array('a[b]=c&a[d]=e', array('a[b]' => 'c', 'a[d]' => 'e')),
            // Ensure it doesn't leave things behind with repeated values
            // Can parse mult-values items
            array('q=a&q=b&q=c', array('q' => array('a', 'b', 'c'))),
        );
    }

    /**
     * @dataProvider parseQueryProvider
     */
    public function testParsesQueries($input, $output)
    {
        $result = Psr7\parse_query($input);
        $this->assertSame($output, $result);
    }

    public function testDoesNotDecode()
    {
        $str = 'foo%20=bar';
        $data = Psr7\parse_query($str, false);
        $this->assertEquals(array('foo%20' => 'bar'), $data);
    }

    /**
     * @dataProvider parseQueryProvider
     */
    public function testParsesAndBuildsQueries($input, $output)
    {
        $result = Psr7\parse_query($input, false);
        $this->assertSame($input, Psr7\build_query($result, false));
    }

    public function testEncodesWithRfc1738()
    {
        $str = Psr7\build_query(array('foo bar' => 'baz+'), PHP_QUERY_RFC1738);
        $this->assertEquals('foo+bar=baz%2B', $str);
    }

    public function testEncodesWithRfc3986()
    {
        $str = Psr7\build_query(array('foo bar' => 'baz+'), PHP_QUERY_RFC3986);
        $this->assertEquals('foo%20bar=baz%2B', $str);
    }

    public function testDoesNotEncode()
    {
        $str = Psr7\build_query(array('foo bar' => 'baz+'), false);
        $this->assertEquals('foo bar=baz+', $str);
    }

    public function testCanControlDecodingType()
    {
        $result = Psr7\parse_query('var=foo+bar', PHP_QUERY_RFC3986);
        $this->assertEquals('foo+bar', $result['var']);
        $result = Psr7\parse_query('var=foo+bar', PHP_QUERY_RFC1738);
        $this->assertEquals('foo bar', $result['var']);
    }

    public function testParsesRequestMessages()
    {
        $req = "GET /abc HTTP/1.0\r\nHost: foo.com\r\nFoo: Bar\r\nBaz: Bam\r\nBaz: Qux\r\n\r\nTest";
        $request = Psr7\parse_request($req);
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/abc', $request->getRequestTarget());
        $this->assertEquals('1.0', $request->getProtocolVersion());
        $this->assertEquals('foo.com', $request->getHeaderLine('Host'));
        $this->assertEquals('Bar', $request->getHeaderLine('Foo'));
        $this->assertEquals('Bam, Qux', $request->getHeaderLine('Baz'));
        $this->assertEquals('Test', (string) $request->getBody());
        $this->assertEquals('http://foo.com/abc', (string) $request->getUri());
    }

    public function testParsesRequestMessagesWithHttpsScheme()
    {
        $req = "PUT /abc?baz=bar HTTP/1.1\r\nHost: foo.com:443\r\n\r\n";
        $request = Psr7\parse_request($req);
        $this->assertEquals('PUT', $request->getMethod());
        $this->assertEquals('/abc?baz=bar', $request->getRequestTarget());
        $this->assertEquals('1.1', $request->getProtocolVersion());
        $this->assertEquals('foo.com:443', $request->getHeaderLine('Host'));
        $this->assertEquals('', (string) $request->getBody());
        $this->assertEquals('https://foo.com/abc?baz=bar', (string) $request->getUri());
    }

    public function testParsesRequestMessagesWithUriWhenHostIsNotFirst()
    {
        $req = "PUT / HTTP/1.1\r\nFoo: Bar\r\nHost: foo.com\r\n\r\n";
        $request = Psr7\parse_request($req);
        $this->assertEquals('PUT', $request->getMethod());
        $this->assertEquals('/', $request->getRequestTarget());
        $this->assertEquals('http://foo.com/', (string) $request->getUri());
    }

    public function testParsesRequestMessagesWithFullUri()
    {
        $req = "GET https://www.google.com:443/search?q=foobar HTTP/1.1\r\nHost: www.google.com\r\n\r\n";
        $request = Psr7\parse_request($req);
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('https://www.google.com:443/search?q=foobar', $request->getRequestTarget());
        $this->assertEquals('1.1', $request->getProtocolVersion());
        $this->assertEquals('www.google.com', $request->getHeaderLine('Host'));
        $this->assertEquals('', (string) $request->getBody());
        $this->assertEquals('https://www.google.com/search?q=foobar', (string) $request->getUri());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testValidatesRequestMessages()
    {
        Psr7\parse_request("HTTP/1.1 200 OK\r\n\r\n");
    }

    public function testParsesResponseMessages()
    {
        $res = "HTTP/1.0 200 OK\r\nFoo: Bar\r\nBaz: Bam\r\nBaz: Qux\r\n\r\nTest";
        $response = Psr7\parse_response($res);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getReasonPhrase());
        $this->assertEquals('1.0', $response->getProtocolVersion());
        $this->assertEquals('Bar', $response->getHeaderLine('Foo'));
        $this->assertEquals('Bam, Qux', $response->getHeaderLine('Baz'));
        $this->assertEquals('Test', (string) $response->getBody());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testValidatesResponseMessages()
    {
        Psr7\parse_response("GET / HTTP/1.1\r\n\r\n");
    }

    public function testDetermineMimetype()
    {
        $this->assertNull(Psr7\mimetype_from_extension('not-a-real-extension'));
        $this->assertEquals(
            'application/json',
            Psr7\mimetype_from_extension('json')
        );
        $this->assertEquals(
            'image/jpeg',
            Psr7\mimetype_from_filename('/tmp/images/IMG034821.JPEG')
        );
    }

    public function testCreatesUriForValue()
    {
        $this->assertInstanceOf('RingCentral\Psr7\Uri', Psr7\uri_for('/foo'));
        $this->assertInstanceOf(
            'RingCentral\Psr7\Uri',
            Psr7\uri_for(new Psr7\Uri('/foo'))
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testValidatesUri()
    {
        Psr7\uri_for(array());
    }

    public function testKeepsPositionOfResource()
    {
        $h = fopen(__FILE__, 'r');
        fseek($h, 10);
        $stream = Psr7\stream_for($h);
        $this->assertEquals(10, $stream->tell());
        $stream->close();
    }

    public function testCreatesWithFactory()
    {
        $stream = Psr7\stream_for('foo');
        $this->assertInstanceOf('RingCentral\Psr7\Stream', $stream);
        $this->assertEquals('foo', $stream->getContents());
        $stream->close();
    }

    public function testFactoryCreatesFromEmptyString()
    {
        $s = Psr7\stream_for();
        $this->assertInstanceOf('RingCentral\Psr7\Stream', $s);
    }

    public function testFactoryCreatesFromNull()
    {
        $s = Psr7\stream_for(null);
        $this->assertInstanceOf('RingCentral\Psr7\Stream', $s);
    }

    public function testFactoryCreatesFromResource()
    {
        $r = fopen(__FILE__, 'r');
        $s = Psr7\stream_for($r);
        $this->assertInstanceOf('RingCentral\Psr7\Stream', $s);
        $this->assertSame(file_get_contents(__FILE__), (string) $s);
    }

    public function testFactoryCreatesFromObjectWithToString()
    {
        $r = new HasToString();
        $s = Psr7\stream_for($r);
        $this->assertInstanceOf('RingCentral\Psr7\Stream', $s);
        $this->assertEquals('foo', (string) $s);
    }

    public function testCreatePassesThrough()
    {
        $s = Psr7\stream_for('foo');
        $this->assertSame($s, Psr7\stream_for($s));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testThrowsExceptionForUnknown()
    {
        Psr7\stream_for(new \stdClass());
    }

    public function testReturnsCustomMetadata()
    {
        $s = Psr7\stream_for('foo', array('metadata' => array('hwm' => 3)));
        $this->assertEquals(3, $s->getMetadata('hwm'));
        $this->assertArrayHasKey('hwm', $s->getMetadata());
    }

    public function testCanSetSize()
    {
        $s = Psr7\stream_for('', array('size' => 10));
        $this->assertEquals(10, $s->getSize());
    }

    public function testCanCreateIteratorBasedStream()
    {
        $a = new \ArrayIterator(array('foo', 'bar', '123'));
        $p = Psr7\stream_for($a);
        $this->assertInstanceOf('RingCentral\Psr7\PumpStream', $p);
        $this->assertEquals('foo', $p->read(3));
        $this->assertFalse($p->eof());
        $this->assertEquals('b', $p->read(1));
        $this->assertEquals('a', $p->read(1));
        $this->assertEquals('r12', $p->read(3));
        $this->assertFalse($p->eof());
        $this->assertEquals('3', $p->getContents());
        $this->assertTrue($p->eof());
        $this->assertEquals(9, $p->tell());
    }

    public function testConvertsRequestsToStrings()
    {
        $request = new Psr7\Request('PUT', 'http://foo.com/hi?123', array(
            'Baz' => 'bar',
            'Qux' => ' ipsum'
        ), 'hello', '1.0');
        $this->assertEquals(
            "PUT /hi?123 HTTP/1.0\r\nHost: foo.com\r\nBaz: bar\r\nQux: ipsum\r\n\r\nhello",
            Psr7\str($request)
        );
    }

    public function testConvertsResponsesToStrings()
    {
        $response = new Psr7\Response(200, array(
            'Baz' => 'bar',
            'Qux' => ' ipsum'
        ), 'hello', '1.0', 'FOO');
        $this->assertEquals(
            "HTTP/1.0 200 FOO\r\nBaz: bar\r\nQux: ipsum\r\n\r\nhello",
            Psr7\str($response)
        );
    }

    public function parseParamsProvider()
    {
        $res1 = array(
            array(
                '<http:/.../front.jpeg>',
                'rel' => 'front',
                'type' => 'image/jpeg',
            ),
            array(
                '<http://.../back.jpeg>',
                'rel' => 'back',
                'type' => 'image/jpeg',
            ),
        );
        return array(
            array(
                '<http:/.../front.jpeg>; rel="front"; type="image/jpeg", <http://.../back.jpeg>; rel=back; type="image/jpeg"',
                $res1
            ),
            array(
                '<http:/.../front.jpeg>; rel="front"; type="image/jpeg",<http://.../back.jpeg>; rel=back; type="image/jpeg"',
                $res1
            ),
            array(
                'foo="baz"; bar=123, boo, test="123", foobar="foo;bar"',
                array(
                    array('foo' => 'baz', 'bar' => '123'),
                    array('boo'),
                    array('test' => '123'),
                    array('foobar' => 'foo;bar')
                )
            ),
            array(
                '<http://.../side.jpeg?test=1>; rel="side"; type="image/jpeg",<http://.../side.jpeg?test=2>; rel=side; type="image/jpeg"',
                array(
                    array('<http://.../side.jpeg?test=1>', 'rel' => 'side', 'type' => 'image/jpeg'),
                    array('<http://.../side.jpeg?test=2>', 'rel' => 'side', 'type' => 'image/jpeg')
                )
            ),
            array(
                '',
                array()
            )
        );
    }
    /**
     * @dataProvider parseParamsProvider
     */
    public function testParseParams($header, $result)
    {
        $this->assertEquals($result, Psr7\parse_header($header));
    }

    public function testParsesArrayHeaders()
    {
        $header = array('a, b', 'c', 'd, e');
        $this->assertEquals(array('a', 'b', 'c', 'd', 'e'), Psr7\normalize_header($header));
    }

    public function testRewindsBody()
    {
        $body = Psr7\stream_for('abc');
        $res = new Psr7\Response(200, array(), $body);
        Psr7\rewind_body($res);
        $this->assertEquals(0, $body->tell());
        $body->rewind(1);
        Psr7\rewind_body($res);
        $this->assertEquals(0, $body->tell());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testThrowsWhenBodyCannotBeRewound()
    {
        $body = Psr7\stream_for('abc');
        $body->read(1);
        $body = FnStream::decorate($body, array(
            'rewind' => function () { throw new \RuntimeException('a'); }
        ));
        $res = new Psr7\Response(200, array(), $body);
        Psr7\rewind_body($res);
    }

    public function testCanModifyRequestWithUri()
    {
        $r1 = new Psr7\Request('GET', 'http://foo.com');
        $r2 = Psr7\modify_request($r1, array(
            'uri' => new Psr7\Uri('http://www.foo.com')
        ));
        $this->assertEquals('http://www.foo.com', (string) $r2->getUri());
        $this->assertEquals('www.foo.com', (string) $r2->getHeaderLine('host'));
    }

    public function testCanModifyRequestWithCaseInsensitiveHeader()
    {
        $r1 = new Psr7\Request('GET', 'http://foo.com', array('User-Agent' => 'foo'));
        $r2 = Psr7\modify_request($r1, array('set_headers' => array('User-agent' => 'bar')));
        $this->assertEquals('bar', $r2->getHeaderLine('User-Agent'));
        $this->assertEquals('bar', $r2->getHeaderLine('User-agent'));
    }

    public function testReturnsAsIsWhenNoChanges()
    {
        $request = new Psr7\Request('GET', 'http://foo.com');
        $this->assertSame($request, Psr7\modify_request($request, array()));
    }

    public function testReturnsUriAsIsWhenNoChanges()
    {
        $r1 = new Psr7\Request('GET', 'http://foo.com');
        $r2 = Psr7\modify_request($r1, array('set_headers' => array('foo' => 'bar')));
        $this->assertNotSame($r1, $r2);
        $this->assertEquals('bar', $r2->getHeaderLine('foo'));
    }

    public function testRemovesHeadersFromMessage()
    {
        $r1 = new Psr7\Request('GET', 'http://foo.com', array('foo' => 'bar'));
        $r2 = Psr7\modify_request($r1, array('remove_headers' => array('foo')));
        $this->assertNotSame($r1, $r2);
        $this->assertFalse($r2->hasHeader('foo'));
    }

    public function testAddsQueryToUri()
    {
        $r1 = new Psr7\Request('GET', 'http://foo.com');
        $r2 = Psr7\modify_request($r1, array('query' => 'foo=bar'));
        $this->assertNotSame($r1, $r2);
        $this->assertEquals('foo=bar', $r2->getUri()->getQuery());
    }

    public function testServerRequestWithServerParams()
    {
        $requestString = "GET /abc HTTP/1.1\r\nHost: foo.com\r\n\r\n";
        $request = Psr7\parse_server_request($requestString);

        $this->assertEquals(array(), $request->getServerParams());
    }

    public function testServerRequestWithoutServerParams()
    {
        $requestString = "GET /abc HTTP/1.1\r\nHost: foo.com\r\n\r\n";
        $serverParams = array('server_address' => '127.0.0.1', 'server_port' => 80);

        $request = Psr7\parse_server_request($requestString, $serverParams);

        $this->assertEquals(array('server_address' => '127.0.0.1', 'server_port' => 80), $request->getServerParams());
    }
}
