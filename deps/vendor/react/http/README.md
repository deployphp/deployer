# HTTP

[![CI status](https://github.com/reactphp/http/actions/workflows/ci.yml/badge.svg)](https://github.com/reactphp/http/actions)
[![installs on Packagist](https://img.shields.io/packagist/dt/react/http?color=blue&label=installs%20on%20Packagist)](https://packagist.org/packages/react/http)

Event-driven, streaming HTTP client and server implementation for [ReactPHP](https://reactphp.org/).

This HTTP library provides re-usable implementations for an HTTP client and
server based on ReactPHP's [`Socket`](https://github.com/reactphp/socket) and
[`EventLoop`](https://github.com/reactphp/event-loop) components.
Its client component allows you to send any number of async HTTP/HTTPS requests
concurrently.
Its server component allows you to build plaintext HTTP and secure HTTPS servers
that accept incoming HTTP requests from HTTP clients (such as web browsers).
This library provides async, streaming means for all of this, so you can handle
multiple concurrent HTTP requests without blocking.

**Table of contents**

* [Quickstart example](#quickstart-example)
* [Client Usage](#client-usage)
    * [Request methods](#request-methods)
    * [Promises](#promises)
    * [Cancellation](#cancellation)
    * [Timeouts](#timeouts)
    * [Authentication](#authentication)
    * [Redirects](#redirects)
    * [Blocking](#blocking)
    * [Concurrency](#concurrency)
    * [Streaming response](#streaming-response)
    * [Streaming request](#streaming-request)
    * [HTTP proxy](#http-proxy)
    * [SOCKS proxy](#socks-proxy)
    * [SSH proxy](#ssh-proxy)
    * [Unix domain sockets](#unix-domain-sockets)
* [Server Usage](#server-usage)
    * [HttpServer](#httpserver)
    * [listen()](#listen)
    * [Server Request](#server-request)
        * [Request parameters](#request-parameters)
        * [Query parameters](#query-parameters)
        * [Request body](#request-body)
        * [Streaming incoming request](#streaming-incoming-request)
        * [Request method](#request-method)
        * [Cookie parameters](#cookie-parameters)
        * [Invalid request](#invalid-request)
    * [Server Response](#server-response)
        * [Deferred response](#deferred-response)
        * [Streaming outgoing response](#streaming-outgoing-response)
        * [Response length](#response-length)
        * [Invalid response](#invalid-response)
        * [Default response headers](#default-response-headers)
    * [Middleware](#middleware)
        * [Custom middleware](#custom-middleware)
        * [Third-Party Middleware](#third-party-middleware)
* [API](#api)
    * [Browser](#browser)
        * [get()](#get)
        * [post()](#post)
        * [head()](#head)
        * [patch()](#patch)
        * [put()](#put)
        * [delete()](#delete)
        * [request()](#request)
        * [requestStreaming()](#requeststreaming)
        * [withTimeout()](#withtimeout)
        * [withFollowRedirects()](#withfollowredirects)
        * [withRejectErrorResponse()](#withrejecterrorresponse)
        * [withBase()](#withbase)
        * [withProtocolVersion()](#withprotocolversion)
        * [withResponseBuffer()](#withresponsebuffer)
        * [withHeader()](#withheader)
        * [withoutHeader()](#withoutheader)
    * [React\Http\Message](#reacthttpmessage)
        * [Response](#response)
            * [html()](#html)
            * [json()](#json)
            * [plaintext()](#plaintext)
            * [xml()](#xml)
        * [Request](#request-1)
        * [ServerRequest](#serverrequest)
        * [Uri](#uri)
        * [ResponseException](#responseexception)
    * [React\Http\Middleware](#reacthttpmiddleware)
        * [StreamingRequestMiddleware](#streamingrequestmiddleware)
        * [LimitConcurrentRequestsMiddleware](#limitconcurrentrequestsmiddleware)
        * [RequestBodyBufferMiddleware](#requestbodybuffermiddleware)
        * [RequestBodyParserMiddleware](#requestbodyparsermiddleware)
* [Install](#install)
* [Tests](#tests)
* [License](#license)

## Quickstart example

Once [installed](#install), you can use the following code to access an
HTTP web server and send some simple HTTP GET requests:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

$client = new React\Http\Browser();

$client->get('http://www.google.com/')->then(function (Psr\Http\Message\ResponseInterface $response) {
    var_dump($response->getHeaders(), (string)$response->getBody());
}, function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});
```

This is an HTTP server which responds with `Hello World!` to every request.

```php
<?php

require __DIR__ . '/vendor/autoload.php';

$http = new React\Http\HttpServer(function (Psr\Http\Message\ServerRequestInterface $request) {
    return React\Http\Message\Response::plaintext(
        "Hello World!\n"
    );
});

$socket = new React\Socket\SocketServer('127.0.0.1:8080');
$http->listen($socket);
```

See also the [examples](examples/).

## Client Usage

### Request methods

Most importantly, this project provides a [`Browser`](#browser) object that
offers several methods that resemble the HTTP protocol methods:

```php
$browser->get($url, array $headers = array());
$browser->head($url, array $headers = array());
$browser->post($url, array $headers = array(), string|ReadableStreamInterface $body = '');
$browser->delete($url, array $headers = array(), string|ReadableStreamInterface $body = '');
$browser->put($url, array $headers = array(), string|ReadableStreamInterface $body = '');
$browser->patch($url, array $headers = array(), string|ReadableStreamInterface $body = '');
```

Each of these methods requires a `$url` and some optional parameters to send an
HTTP request. Each of these method names matches the respective HTTP request
method, for example the [`get()`](#get) method sends an HTTP `GET` request.

You can optionally pass an associative array of additional `$headers` that will be
sent with this HTTP request. Additionally, each method will automatically add a
matching `Content-Length` request header if an outgoing request body is given and its
size is known and non-empty. For an empty request body, if will only include a
`Content-Length: 0` request header if the request method usually expects a request
body (only applies to `POST`, `PUT` and `PATCH` HTTP request methods).

If you're using a [streaming request body](#streaming-request), it will default
to using `Transfer-Encoding: chunked` unless you explicitly pass in a matching `Content-Length`
request header. See also [streaming request](#streaming-request) for more details.

By default, all of the above methods default to sending requests using the
HTTP/1.1 protocol version. If you want to explicitly use the legacy HTTP/1.0
protocol version, you can use the [`withProtocolVersion()`](#withprotocolversion)
method. If you want to use any other or even custom HTTP request method, you can
use the [`request()`](#request) method.

Each of the above methods supports async operation and either *fulfills* with a
[PSR-7 `ResponseInterface`](https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface)
or *rejects* with an `Exception`.
Please see the following chapter about [promises](#promises) for more details.

### Promises

Sending requests is async (non-blocking), so you can actually send multiple
requests in parallel.
The `Browser` will respond to each request with a
[PSR-7 `ResponseInterface`](https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface)
message, the order is not guaranteed.
Sending requests uses a [Promise](https://github.com/reactphp/promise)-based
interface that makes it easy to react to when an HTTP request is completed
(i.e. either successfully fulfilled or rejected with an error):

```php
$browser->get($url)->then(
    function (Psr\Http\Message\ResponseInterface $response) {
        var_dump('Response received', $response);
    },
    function (Exception $e) {
        echo 'Error: ' . $e->getMessage() . PHP_EOL;
    }
);
```

If this looks strange to you, you can also use the more traditional [blocking API](#blocking).

Keep in mind that resolving the Promise with the full response message means the
whole response body has to be kept in memory.
This is easy to get started and works reasonably well for smaller responses
(such as common HTML pages or RESTful or JSON API requests).

You may also want to look into the [streaming API](#streaming-response):

* If you're dealing with lots of concurrent requests (100+) or
* If you want to process individual data chunks as they happen (without having to wait for the full response body) or
* If you're expecting a big response body size (1 MiB or more, for example when downloading binary files) or
* If you're unsure about the response body size (better be safe than sorry when accessing arbitrary remote HTTP endpoints and the response body size is unknown in advance).

### Cancellation

The returned Promise is implemented in such a way that it can be cancelled
when it is still pending.
Cancelling a pending promise will reject its value with an Exception and
clean up any underlying resources.

```php
$promise = $browser->get($url);

Loop::addTimer(2.0, function () use ($promise) {
    $promise->cancel();
});
```

### Timeouts

This library uses a very efficient HTTP implementation, so most HTTP requests
should usually be completed in mere milliseconds. However, when sending HTTP
requests over an unreliable network (the internet), there are a number of things
that can go wrong and may cause the request to fail after a time. As such, this
library respects PHP's `default_socket_timeout` setting (default 60s) as a timeout
for sending the outgoing HTTP request and waiting for a successful response and
will otherwise cancel the pending request and reject its value with an Exception.

Note that this timeout value covers creating the underlying transport connection,
sending the HTTP request, receiving the HTTP response headers and its full
response body and following any eventual [redirects](#redirects). See also
[redirects](#redirects) below to configure the number of redirects to follow (or
disable following redirects altogether) and also [streaming](#streaming-response)
below to not take receiving large response bodies into account for this timeout.

You can use the [`withTimeout()` method](#withtimeout) to pass a custom timeout
value in seconds like this:

```php
$browser = $browser->withTimeout(10.0);

$browser->get($url)->then(function (Psr\Http\Message\ResponseInterface $response) {
    // response received within 10 seconds maximum
    var_dump($response->getHeaders());
}, function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});
```

Similarly, you can use a bool `false` to not apply a timeout at all
or use a bool `true` value to restore the default handling.
See [`withTimeout()`](#withtimeout) for more details.

If you're using a [streaming response body](#streaming-response), the time it
takes to receive the response body stream will not be included in the timeout.
This allows you to keep this incoming stream open for a longer time, such as
when downloading a very large stream or when streaming data over a long-lived
connection.

If you're using a [streaming request body](#streaming-request), the time it
takes to send the request body stream will not be included in the timeout. This
allows you to keep this outgoing stream open for a longer time, such as when
uploading a very large stream.

Note that this timeout handling applies to the higher-level HTTP layer. Lower
layers such as socket and DNS may also apply (different) timeout values. In
particular, the underlying socket connection uses the same `default_socket_timeout`
setting to establish the underlying transport connection. To control this
connection timeout behavior, you can [inject a custom `Connector`](#browser)
like this:

```php
$browser = new React\Http\Browser(
    new React\Socket\Connector(
        array(
            'timeout' => 5
        )
    )
);
```

### Authentication

This library supports [HTTP Basic Authentication](https://en.wikipedia.org/wiki/Basic_access_authentication)
using the `Authorization: Basic …` request header or allows you to set an explicit
`Authorization` request header.

By default, this library does not include an outgoing `Authorization` request
header. If the server requires authentication, if may return a `401` (Unauthorized)
status code which will reject the request by default (see also the
[`withRejectErrorResponse()` method](#withrejecterrorresponse) below).

In order to pass authentication details, you can simply pass the username and
password as part of the request URL like this:

```php
$promise = $browser->get('https://user:pass@example.com/api');
```

Note that special characters in the authentication details have to be
percent-encoded, see also [`rawurlencode()`](https://www.php.net/manual/en/function.rawurlencode.php).
This example will automatically pass the base64-encoded authentication details
using the outgoing `Authorization: Basic …` request header. If the HTTP endpoint
you're talking to requires any other authentication scheme, you can also pass
this header explicitly. This is common when using (RESTful) HTTP APIs that use
OAuth access tokens or JSON Web Tokens (JWT):

```php
$token = 'abc123';

$promise = $browser->get(
    'https://example.com/api',
    array(
        'Authorization' => 'Bearer ' . $token
    )
);
```

When following redirects, the `Authorization` request header will never be sent
to any remote hosts by default. When following a redirect where the `Location`
response header contains authentication details, these details will be sent for
following requests. See also [redirects](#redirects) below.

### Redirects

By default, this library follows any redirects and obeys `3xx` (Redirection)
status codes using the `Location` response header from the remote server.
The promise will be fulfilled with the last response from the chain of redirects.

```php
$browser->get($url, $headers)->then(function (Psr\Http\Message\ResponseInterface $response) {
    // the final response will end up here
    var_dump($response->getHeaders());
}, function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});
```

Any redirected requests will follow the semantics of the original request and
will include the same request headers as the original request except for those
listed below.
If the original request is a temporary (307) or a permanent (308) redirect, request
body and headers will be passed to the redirected request. Otherwise, the request 
body will never be passed to the redirected request. Accordingly, each redirected 
request will remove any `Content-Length` and `Content-Type` request headers.

If the original request used HTTP authentication with an `Authorization` request
header, this request header will only be passed as part of the redirected
request if the redirected URL is using the same host. In other words, the
`Authorizaton` request header will not be forwarded to other foreign hosts due to
possible privacy/security concerns. When following a redirect where the `Location`
response header contains authentication details, these details will be sent for
following requests.

You can use the [`withFollowRedirects()`](#withfollowredirects) method to
control the maximum number of redirects to follow or to return any redirect
responses as-is and apply custom redirection logic like this:

```php
$browser = $browser->withFollowRedirects(false);

$browser->get($url)->then(function (Psr\Http\Message\ResponseInterface $response) {
    // any redirects will now end up here
    var_dump($response->getHeaders());
}, function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});
```

See also [`withFollowRedirects()`](#withfollowredirects) for more details.

### Blocking

As stated above, this library provides you a powerful, async API by default.

You can also integrate this into your traditional, blocking environment by using
[reactphp/async](https://github.com/reactphp/async). This allows you to simply
await async HTTP requests like this:

```php
use function React\Async\await;

$browser = new React\Http\Browser();

$promise = $browser->get('http://example.com/');

try {
    $response = await($promise);
    // response successfully received
} catch (Exception $e) {
    // an error occurred while performing the request
}
```

Similarly, you can also process multiple requests concurrently and await an array of `Response` objects:

```php
use function React\Async\await;
use function React\Promise\all;

$promises = array(
    $browser->get('http://example.com/'),
    $browser->get('http://www.example.org/'),
);

$responses = await(all($promises));
```

This is made possible thanks to fibers available in PHP 8.1+ and our
compatibility API that also works on all supported PHP versions.
Please refer to [reactphp/async](https://github.com/reactphp/async#readme) for more details.

Keep in mind the above remark about buffering the whole response message in memory.
As an alternative, you may also see one of the following chapters for the
[streaming API](#streaming-response).

### Concurrency

As stated above, this library provides you a powerful, async API. Being able to
send a large number of requests at once is one of the core features of this
project. For instance, you can easily send 100 requests concurrently while
processing SQL queries at the same time.

Remember, with great power comes great responsibility. Sending an excessive
number of requests may either take up all resources on your side or it may even
get you banned by the remote side if it sees an unreasonable number of requests
from your side.

```php
// watch out if array contains many elements
foreach ($urls as $url) {
    $browser->get($url)->then(function (Psr\Http\Message\ResponseInterface $response) {
        var_dump($response->getHeaders());
    }, function (Exception $e) {
        echo 'Error: ' . $e->getMessage() . PHP_EOL;
    });
}
```

As a consequence, it's usually recommended to limit concurrency on the sending
side to a reasonable value. It's common to use a rather small limit, as doing
more than a dozen of things at once may easily overwhelm the receiving side. You
can use [clue/reactphp-mq](https://github.com/clue/reactphp-mq) as a lightweight
in-memory queue to concurrently do many (but not too many) things at once:

```php
// wraps Browser in a Queue object that executes no more than 10 operations at once
$q = new Clue\React\Mq\Queue(10, null, function ($url) use ($browser) {
    return $browser->get($url);
});

foreach ($urls as $url) {
    $q($url)->then(function (Psr\Http\Message\ResponseInterface $response) {
        var_dump($response->getHeaders());
    }, function (Exception $e) {
        echo 'Error: ' . $e->getMessage() . PHP_EOL;
    });
}
```

Additional requests that exceed the concurrency limit will automatically be
enqueued until one of the pending requests completes. This integrates nicely
with the existing [Promise-based API](#promises). Please refer to
[clue/reactphp-mq](https://github.com/clue/reactphp-mq) for more details.

This in-memory approach works reasonably well for some thousand outstanding
requests. If you're processing a very large input list (think millions of rows
in a CSV or NDJSON file), you may want to look into using a streaming approach
instead. See [clue/reactphp-flux](https://github.com/clue/reactphp-flux) for
more details.

### Streaming response

All of the above examples assume you want to store the whole response body in memory.
This is easy to get started and works reasonably well for smaller responses.

However, there are several situations where it's usually a better idea to use a
streaming approach, where only small chunks have to be kept in memory:

* If you're dealing with lots of concurrent requests (100+) or
* If you want to process individual data chunks as they happen (without having to wait for the full response body) or
* If you're expecting a big response body size (1 MiB or more, for example when downloading binary files) or
* If you're unsure about the response body size (better be safe than sorry when accessing arbitrary remote HTTP endpoints and the response body size is unknown in advance). 

You can use the [`requestStreaming()`](#requeststreaming) method to send an
arbitrary HTTP request and receive a streaming response. It uses the same HTTP
message API, but does not buffer the response body in memory. It only processes
the response body in small chunks as data is received and forwards this data
through [ReactPHP's Stream API](https://github.com/reactphp/stream). This works
for (any number of) responses of arbitrary sizes.

This means it resolves with a normal
[PSR-7 `ResponseInterface`](https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface),
which can be used to access the response message parameters as usual.
You can access the message body as usual, however it now also
implements [ReactPHP's `ReadableStreamInterface`](https://github.com/reactphp/stream#readablestreaminterface)
as well as parts of the [PSR-7 `StreamInterface`](https://www.php-fig.org/psr/psr-7/#34-psrhttpmessagestreaminterface).

```php
$browser->requestStreaming('GET', $url)->then(function (Psr\Http\Message\ResponseInterface $response) {
    $body = $response->getBody();
    assert($body instanceof Psr\Http\Message\StreamInterface);
    assert($body instanceof React\Stream\ReadableStreamInterface);

    $body->on('data', function ($chunk) {
        echo $chunk;
    });

    $body->on('error', function (Exception $e) {
        echo 'Error: ' . $e->getMessage() . PHP_EOL;
    });

    $body->on('close', function () {
        echo '[DONE]' . PHP_EOL;
    });
}, function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});
```

See also the [stream download benchmark example](examples/91-client-benchmark-download.php) and
the [stream forwarding example](examples/21-client-request-streaming-to-stdout.php).

You can invoke the following methods on the message body:

```php
$body->on($event, $callback);
$body->eof();
$body->isReadable();
$body->pipe(React\Stream\WritableStreamInterface $dest, array $options = array());
$body->close();
$body->pause();
$body->resume();
```

Because the message body is in a streaming state, invoking the following methods
doesn't make much sense:

```php
$body->__toString(); // ''
$body->detach(); // throws BadMethodCallException
$body->getSize(); // null
$body->tell(); // throws BadMethodCallException
$body->isSeekable(); // false
$body->seek(); // throws BadMethodCallException
$body->rewind(); // throws BadMethodCallException
$body->isWritable(); // false
$body->write(); // throws BadMethodCallException
$body->read(); // throws BadMethodCallException
$body->getContents(); // throws BadMethodCallException
```

Note how [timeouts](#timeouts) apply slightly differently when using streaming.
In streaming mode, the timeout value covers creating the underlying transport
connection, sending the HTTP request, receiving the HTTP response headers and
following any eventual [redirects](#redirects). In particular, the timeout value
does not take receiving (possibly large) response bodies into account.

If you want to integrate the streaming response into a higher level API, then
working with Promise objects that resolve with Stream objects is often inconvenient.
Consider looking into also using [react/promise-stream](https://github.com/reactphp/promise-stream).
The resulting streaming code could look something like this:

```php
use React\Promise\Stream;

function download(Browser $browser, string $url): React\Stream\ReadableStreamInterface {
    return Stream\unwrapReadable(
        $browser->requestStreaming('GET', $url)->then(function (Psr\Http\Message\ResponseInterface $response) {
            return $response->getBody();
        })
    );
}

$stream = download($browser, $url);
$stream->on('data', function ($data) {
    echo $data;
});
$stream->on('error', function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});
```

See also the [`requestStreaming()`](#requeststreaming) method for more details.

### Streaming request

Besides streaming the response body, you can also stream the request body.
This can be useful if you want to send big POST requests (uploading files etc.)
or process many outgoing streams at once.
Instead of passing the body as a string, you can simply pass an instance
implementing [ReactPHP's `ReadableStreamInterface`](https://github.com/reactphp/stream#readablestreaminterface)
to the [request methods](#request-methods) like this:

```php
$browser->post($url, array(), $stream)->then(function (Psr\Http\Message\ResponseInterface $response) {
    echo 'Successfully sent.';
}, function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});
```

If you're using a streaming request body (`React\Stream\ReadableStreamInterface`), it will
default to using `Transfer-Encoding: chunked` or you have to explicitly pass in a
matching `Content-Length` request header like so:

```php
$body = new React\Stream\ThroughStream();
Loop::addTimer(1.0, function () use ($body) {
    $body->end("hello world");
});

$browser->post($url, array('Content-Length' => '11'), $body);
```

If the streaming request body emits an `error` event or is explicitly closed
without emitting a successful `end` event first, the request will automatically
be closed and rejected.

### HTTP proxy

You can also establish your outgoing connections through an HTTP CONNECT proxy server
by adding a dependency to [clue/reactphp-http-proxy](https://github.com/clue/reactphp-http-proxy).

HTTP CONNECT proxy servers (also commonly known as "HTTPS proxy" or "SSL proxy")
are commonly used to tunnel HTTPS traffic through an intermediary ("proxy"), to
conceal the origin address (anonymity) or to circumvent address blocking
(geoblocking). While many (public) HTTP CONNECT proxy servers often limit this
to HTTPS port `443` only, this can technically be used to tunnel any TCP/IP-based
protocol, such as plain HTTP and TLS-encrypted HTTPS.

```php
$proxy = new Clue\React\HttpProxy\ProxyConnector('127.0.0.1:8080');

$connector = new React\Socket\Connector(array(
    'tcp' => $proxy,
    'dns' => false
));

$browser = new React\Http\Browser($connector);
```

See also the [HTTP proxy example](examples/11-client-http-proxy.php).

### SOCKS proxy

You can also establish your outgoing connections through a SOCKS proxy server
by adding a dependency to [clue/reactphp-socks](https://github.com/clue/reactphp-socks).

The SOCKS proxy protocol family (SOCKS5, SOCKS4 and SOCKS4a) is commonly used to
tunnel HTTP(S) traffic through an intermediary ("proxy"), to conceal the origin
address (anonymity) or to circumvent address blocking (geoblocking). While many
(public) SOCKS proxy servers often limit this to HTTP(S) port `80` and `443`
only, this can technically be used to tunnel any TCP/IP-based protocol.

```php
$proxy = new Clue\React\Socks\Client('127.0.0.1:1080');

$connector = new React\Socket\Connector(array(
    'tcp' => $proxy,
    'dns' => false
));

$browser = new React\Http\Browser($connector);
```

See also the [SOCKS proxy example](examples/12-client-socks-proxy.php).

### SSH proxy

You can also establish your outgoing connections through an SSH server
by adding a dependency to [clue/reactphp-ssh-proxy](https://github.com/clue/reactphp-ssh-proxy).

[Secure Shell (SSH)](https://en.wikipedia.org/wiki/Secure_Shell) is a secure
network protocol that is most commonly used to access a login shell on a remote
server. Its architecture allows it to use multiple secure channels over a single
connection. Among others, this can also be used to create an "SSH tunnel", which
is commonly used to tunnel HTTP(S) traffic through an intermediary ("proxy"), to
conceal the origin address (anonymity) or to circumvent address blocking
(geoblocking). This can be used to tunnel any TCP/IP-based protocol (HTTP, SMTP,
IMAP etc.), allows you to access local services that are otherwise not accessible
from the outside (database behind firewall) and as such can also be used for
plain HTTP and TLS-encrypted HTTPS.

```php
$proxy = new Clue\React\SshProxy\SshSocksConnector('alice@example.com');

$connector = new React\Socket\Connector(array(
    'tcp' => $proxy,
    'dns' => false
));

$browser = new React\Http\Browser($connector);
```

See also the [SSH proxy example](examples/13-client-ssh-proxy.php).

### Unix domain sockets

By default, this library supports transport over plaintext TCP/IP and secure
TLS connections for the `http://` and `https://` URL schemes respectively.
This library also supports Unix domain sockets (UDS) when explicitly configured.

In order to use a UDS path, you have to explicitly configure the connector to
override the destination URL so that the hostname given in the request URL will
no longer be used to establish the connection:

```php
$connector = new React\Socket\FixedUriConnector(
    'unix:///var/run/docker.sock',
    new React\Socket\UnixConnector()
);

$browser = new React\Http\Browser($connector);

$client->get('http://localhost/info')->then(function (Psr\Http\Message\ResponseInterface $response) {
    var_dump($response->getHeaders(), (string)$response->getBody());
}, function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});
```

See also the [Unix Domain Sockets (UDS) example](examples/14-client-unix-domain-sockets.php).


## Server Usage

### HttpServer

<a id="server"></a> <!-- legacy id -->

The `React\Http\HttpServer` class is responsible for handling incoming connections and then
processing each incoming HTTP request.

When a complete HTTP request has been received, it will invoke the given
request handler function. This request handler function needs to be passed to
the constructor and will be invoked with the respective [request](#server-request)
object and expects a [response](#server-response) object in return:

```php
$http = new React\Http\HttpServer(function (Psr\Http\Message\ServerRequestInterface $request) {
    return React\Http\Message\Response::plaintext(
        "Hello World!\n"
    );
});
```

Each incoming HTTP request message is always represented by the
[PSR-7 `ServerRequestInterface`](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface),
see also following [request](#server-request) chapter for more details.

Each outgoing HTTP response message is always represented by the
[PSR-7 `ResponseInterface`](https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface),
see also following [response](#server-response) chapter for more details.

This class takes an optional `LoopInterface|null $loop` parameter that can be used to
pass the event loop instance to use for this object. You can use a `null` value
here in order to use the [default loop](https://github.com/reactphp/event-loop#loop).
This value SHOULD NOT be given unless you're sure you want to explicitly use a
given event loop instance.

In order to start listening for any incoming connections, the `HttpServer` needs
to be attached to an instance of
[`React\Socket\ServerInterface`](https://github.com/reactphp/socket#serverinterface)
through the [`listen()`](#listen) method as described in the following
chapter. In its most simple form, you can attach this to a
[`React\Socket\SocketServer`](https://github.com/reactphp/socket#socketserver)
in order to start a plaintext HTTP server like this:

```php
$http = new React\Http\HttpServer($handler);

$socket = new React\Socket\SocketServer('0.0.0.0:8080');
$http->listen($socket);
```

See also the [`listen()`](#listen) method and the
[hello world server example](examples/51-server-hello-world.php)
for more details.

By default, the `HttpServer` buffers and parses the complete incoming HTTP
request in memory. It will invoke the given request handler function when the
complete request headers and request body has been received. This means the
[request](#server-request) object passed to your request handler function will be
fully compatible with PSR-7 (http-message). This provides sane defaults for
80% of the use cases and is the recommended way to use this library unless
you're sure you know what you're doing.

On the other hand, buffering complete HTTP requests in memory until they can
be processed by your request handler function means that this class has to
employ a number of limits to avoid consuming too much memory. In order to
take the more advanced configuration out your hand, it respects setting from
your [`php.ini`](https://www.php.net/manual/en/ini.core.php) to apply its
default settings. This is a list of PHP settings this class respects with
their respective default values:

```
memory_limit 128M
post_max_size 8M // capped at 64K

enable_post_data_reading 1
max_input_nesting_level 64
max_input_vars 1000

file_uploads 1
upload_max_filesize 2M
max_file_uploads 20
```

In particular, the `post_max_size` setting limits how much memory a single
HTTP request is allowed to consume while buffering its request body. This
needs to be limited because the server can process a large number of requests
concurrently, so the server may potentially consume a large amount of memory
otherwise. To support higher concurrency by default, this value is capped
at `64K`. If you assign a higher value, it will only allow `64K` by default.
If a request exceeds this limit, its request body will be ignored and it will
be processed like a request with no request body at all. See below for
explicit configuration to override this setting.

By default, this class will try to avoid consuming more than half of your
`memory_limit` for buffering multiple concurrent HTTP requests. As such, with
the above default settings of `128M` max, it will try to consume no more than
`64M` for buffering multiple concurrent HTTP requests. As a consequence, it
will limit the concurrency to `1024` HTTP requests with the above defaults.

It is imperative that you assign reasonable values to your PHP ini settings.
It is usually recommended to not support buffering incoming HTTP requests
with a large HTTP request body (e.g. large file uploads). If you want to
increase this buffer size, you will have to also increase the total memory
limit to allow for more concurrent requests (set `memory_limit 512M` or more)
or explicitly limit concurrency.

In order to override the above buffering defaults, you can configure the
`HttpServer` explicitly. You can use the
[`LimitConcurrentRequestsMiddleware`](#limitconcurrentrequestsmiddleware) and
[`RequestBodyBufferMiddleware`](#requestbodybuffermiddleware) (see below)
to explicitly configure the total number of requests that can be handled at
once like this:

```php
$http = new React\Http\HttpServer(
    new React\Http\Middleware\StreamingRequestMiddleware(),
    new React\Http\Middleware\LimitConcurrentRequestsMiddleware(100), // 100 concurrent buffering handlers
    new React\Http\Middleware\RequestBodyBufferMiddleware(2 * 1024 * 1024), // 2 MiB per request
    new React\Http\Middleware\RequestBodyParserMiddleware(),
    $handler
);
```

In this example, we allow processing up to 100 concurrent requests at once
and each request can buffer up to `2M`. This means you may have to keep a
maximum of `200M` of memory for incoming request body buffers. Accordingly,
you need to adjust the `memory_limit` ini setting to allow for these buffers
plus your actual application logic memory requirements (think `512M` or more).

> Internally, this class automatically assigns these middleware handlers
  automatically when no [`StreamingRequestMiddleware`](#streamingrequestmiddleware)
  is given. Accordingly, you can use this example to override all default
  settings to implement custom limits.

As an alternative to buffering the complete request body in memory, you can
also use a streaming approach where only small chunks of data have to be kept
in memory:

```php
$http = new React\Http\HttpServer(
    new React\Http\Middleware\StreamingRequestMiddleware(),
    $handler
);
```

In this case, it will invoke the request handler function once the HTTP
request headers have been received, i.e. before receiving the potentially
much larger HTTP request body. This means the [request](#server-request) passed to
your request handler function may not be fully compatible with PSR-7. This is
specifically designed to help with more advanced use cases where you want to
have full control over consuming the incoming HTTP request body and
concurrency settings. See also [streaming incoming request](#streaming-incoming-request)
below for more details.

> Changelog v1.5.0: This class has been renamed to `HttpServer` from the
  previous `Server` class in order to avoid any ambiguities.
  The previous name has been deprecated and should not be used anymore.

### listen()

The `listen(React\Socket\ServerInterface $socket): void` method can be used to
start listening for HTTP requests on the given socket server instance.

The given [`React\Socket\ServerInterface`](https://github.com/reactphp/socket#serverinterface)
is responsible for emitting the underlying streaming connections. This
HTTP server needs to be attached to it in order to process any
connections and pase incoming streaming data as incoming HTTP request
messages. In its most common form, you can attach this to a
[`React\Socket\SocketServer`](https://github.com/reactphp/socket#socketserver)
in order to start a plaintext HTTP server like this:

```php
$http = new React\Http\HttpServer($handler);

$socket = new React\Socket\SocketServer('0.0.0.0:8080');
$http->listen($socket);
```

See also [hello world server example](examples/51-server-hello-world.php)
for more details.

This example will start listening for HTTP requests on the alternative
HTTP port `8080` on all interfaces (publicly). As an alternative, it is
very common to use a reverse proxy and let this HTTP server listen on the
localhost (loopback) interface only by using the listen address
`127.0.0.1:8080` instead. This way, you host your application(s) on the
default HTTP port `80` and only route specific requests to this HTTP
server.

Likewise, it's usually recommended to use a reverse proxy setup to accept
secure HTTPS requests on default HTTPS port `443` (TLS termination) and
only route plaintext requests to this HTTP server. As an alternative, you
can also accept secure HTTPS requests with this HTTP server by attaching
this to a [`React\Socket\SocketServer`](https://github.com/reactphp/socket#socketserver)
using a secure TLS listen address, a certificate file and optional
`passphrase` like this:

```php
$http = new React\Http\HttpServer($handler);

$socket = new React\Socket\SocketServer('tls://0.0.0.0:8443', array(
    'tls' => array(
        'local_cert' => __DIR__ . '/localhost.pem'
    )
));
$http->listen($socket);
```

See also [hello world HTTPS example](examples/61-server-hello-world-https.php)
for more details.

### Server Request

As seen above, the [`HttpServer`](#httpserver) class is responsible for handling
incoming connections and then processing each incoming HTTP request.

The request object will be processed once the request has
been received by the client.
This request object implements the
[PSR-7 `ServerRequestInterface`](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface)
which in turn extends the
[PSR-7 `RequestInterface`](https://www.php-fig.org/psr/psr-7/#32-psrhttpmessagerequestinterface)
and will be passed to the callback function like this.

 ```php 
$http = new React\Http\HttpServer(function (Psr\Http\Message\ServerRequestInterface $request) {
    $body = "The method of the request is: " . $request->getMethod() . "\n";
    $body .= "The requested path is: " . $request->getUri()->getPath() . "\n";

    return React\Http\Message\Response::plaintext(
        $body
    );
});
```

For more details about the request object, also check out the documentation of
[PSR-7 `ServerRequestInterface`](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface)
and
[PSR-7 `RequestInterface`](https://www.php-fig.org/psr/psr-7/#32-psrhttpmessagerequestinterface).

#### Request parameters

The `getServerParams(): mixed[]` method can be used to
get server-side parameters similar to the `$_SERVER` variable.
The following parameters are currently available:

* `REMOTE_ADDR`
  The IP address of the request sender
* `REMOTE_PORT`
  Port of the request sender
* `SERVER_ADDR`
  The IP address of the server
* `SERVER_PORT`
  The port of the server
* `REQUEST_TIME`
  Unix timestamp when the complete request header has been received,
  as integer similar to `time()`
* `REQUEST_TIME_FLOAT`
  Unix timestamp when the complete request header has been received,
  as float similar to `microtime(true)`
* `HTTPS`
  Set to 'on' if the request used HTTPS, otherwise it won't be set

```php 
$http = new React\Http\HttpServer(function (Psr\Http\Message\ServerRequestInterface $request) {
    $body = "Your IP is: " . $request->getServerParams()['REMOTE_ADDR'] . "\n";

    return React\Http\Message\Response::plaintext(
        $body
    );
});
```

See also [whatsmyip server example](examples/53-server-whatsmyip.php).

> Advanced: Note that address parameters will not be set if you're listening on
  a Unix domain socket (UDS) path as this protocol lacks the concept of
  host/port.

#### Query parameters

The `getQueryParams(): array` method can be used to get the query parameters
similiar to the `$_GET` variable.

```php
$http = new React\Http\HttpServer(function (Psr\Http\Message\ServerRequestInterface $request) {
    $queryParams = $request->getQueryParams();

    $body = 'The query parameter "foo" is not set. Click the following link ';
    $body .= '<a href="/?foo=bar">to use query parameter in your request</a>';

    if (isset($queryParams['foo'])) {
        $body = 'The value of "foo" is: ' . htmlspecialchars($queryParams['foo']);
    }

    return React\Http\Message\Response::html(
        $body
    );
});
```

The response in the above example will return a response body with a link.
The URL contains the query parameter `foo` with the value `bar`.
Use [`htmlentities`](https://www.php.net/manual/en/function.htmlentities.php)
like in this example to prevent
[Cross-Site Scripting (abbreviated as XSS)](https://en.wikipedia.org/wiki/Cross-site_scripting).

See also [server query parameters example](examples/54-server-query-parameter.php).

#### Request body

By default, the [`Server`](#httpserver) will buffer and parse the full request body
in memory. This means the given request object includes the parsed request body
and any file uploads.

> As an alternative to the default buffering logic, you can also use the
  [`StreamingRequestMiddleware`](#streamingrequestmiddleware). Jump to the next
  chapter to learn more about how to process a
  [streaming incoming request](#streaming-incoming-request).

As stated above, each incoming HTTP request is always represented by the
[PSR-7 `ServerRequestInterface`](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface).
This interface provides several methods that are useful when working with the
incoming request body as described below.

The `getParsedBody(): null|array|object` method can be used to
get the parsed request body, similar to
[PHP's `$_POST` variable](https://www.php.net/manual/en/reserved.variables.post.php).
This method may return a (possibly nested) array structure with all body
parameters or a `null` value if the request body could not be parsed.
By default, this method will only return parsed data for requests using
`Content-Type: application/x-www-form-urlencoded` or `Content-Type: multipart/form-data`
request headers (commonly used for `POST` requests for HTML form submission data).

```php
$http = new React\Http\HttpServer(function (Psr\Http\Message\ServerRequestInterface $request) {
    $name = $request->getParsedBody()['name'] ?? 'anonymous';

    return React\Http\Message\Response::plaintext(
        "Hello $name!\n"
    );
});
```

See also [form upload example](examples/62-server-form-upload.php) for more details.

The `getBody(): StreamInterface` method can be used to
get the raw data from this request body, similar to
[PHP's `php://input` stream](https://www.php.net/manual/en/wrappers.php.php#wrappers.php.input).
This method returns an instance of the request body represented by the
[PSR-7 `StreamInterface`](https://www.php-fig.org/psr/psr-7/#34-psrhttpmessagestreaminterface).
This is particularly useful when using a custom request body that will not
otherwise be parsed by default, such as a JSON (`Content-Type: application/json`) or
an XML (`Content-Type: application/xml`) request body (which is commonly used for
`POST`, `PUT` or `PATCH` requests in JSON-based or RESTful/RESTish APIs).

```php
$http = new React\Http\HttpServer(function (Psr\Http\Message\ServerRequestInterface $request) {
    $data = json_decode((string)$request->getBody());
    $name = $data->name ?? 'anonymous';

    return React\Http\Message\Response::json(
        ['message' => "Hello $name!"]
    );
});
```

See also [JSON API server example](examples/59-server-json-api.php) for more details.

The `getUploadedFiles(): array` method can be used to
get the uploaded files in this request, similar to
[PHP's `$_FILES` variable](https://www.php.net/manual/en/reserved.variables.files.php).
This method returns a (possibly nested) array structure with all file uploads, each represented by the
[PSR-7 `UploadedFileInterface`](https://www.php-fig.org/psr/psr-7/#36-psrhttpmessageuploadedfileinterface).
This array will only be filled when using the `Content-Type: multipart/form-data`
request header (commonly used for `POST` requests for HTML file uploads).

```php
$http = new React\Http\HttpServer(function (Psr\Http\Message\ServerRequestInterface $request) {
    $files = $request->getUploadedFiles();
    $name = isset($files['avatar']) ? $files['avatar']->getClientFilename() : 'nothing';

    return React\Http\Message\Response::plaintext(
        "Uploaded $name\n"
    );
});
```

See also [form upload server example](examples/62-server-form-upload.php) for more details.

The `getSize(): ?int` method can be used to
get the size of the request body, similar to PHP's `$_SERVER['CONTENT_LENGTH']` variable.
This method returns the complete size of the request body measured in number
of bytes as defined by the message boundaries.
This value may be `0` if the request message does not contain a request body
(such as a simple `GET` request).
This method operates on the buffered request body, i.e. the request body size
is always known, even when the request does not specify a `Content-Length` request
header or when using `Transfer-Encoding: chunked` for HTTP/1.1 requests.

> Note: The `HttpServer` automatically takes care of handling requests with the
  additional `Expect: 100-continue` request header. When HTTP/1.1 clients want to
  send a bigger request body, they MAY send only the request headers with an
  additional `Expect: 100-continue` request header and wait before sending the actual
  (large) message body. In this case the server will automatically send an
  intermediary `HTTP/1.1 100 Continue` response to the client. This ensures you
  will receive the request body without a delay as expected.

#### Streaming incoming request

If you're using the advanced [`StreamingRequestMiddleware`](#streamingrequestmiddleware),
the request object will be processed once the request headers have been received.
This means that this happens irrespective of (i.e. *before*) receiving the
(potentially much larger) request body.

> Note that this is non-standard behavior considered advanced usage. Jump to the
  previous chapter to learn more about how to process a buffered [request body](#request-body).

While this may be uncommon in the PHP ecosystem, this is actually a very powerful
approach that gives you several advantages not otherwise possible:

* React to requests *before* receiving a large request body,
  such as rejecting an unauthenticated request or one that exceeds allowed
  message lengths (file uploads).
* Start processing parts of the request body before the remainder of the request
  body arrives or if the sender is slowly streaming data.
* Process a large request body without having to buffer anything in memory,
  such as accepting a huge file upload or possibly unlimited request body stream.

The `getBody(): StreamInterface` method can be used to
access the request body stream.
In the streaming mode, this method returns a stream instance that implements both the
[PSR-7 `StreamInterface`](https://www.php-fig.org/psr/psr-7/#34-psrhttpmessagestreaminterface)
and the [ReactPHP `ReadableStreamInterface`](https://github.com/reactphp/stream#readablestreaminterface).
However, most of the
[PSR-7 `StreamInterface`](https://www.php-fig.org/psr/psr-7/#34-psrhttpmessagestreaminterface)
methods have been designed under the assumption of being in control of a
synchronous request body.
Given that this does not apply to this server, the following
[PSR-7 `StreamInterface`](https://www.php-fig.org/psr/psr-7/#34-psrhttpmessagestreaminterface)
methods are not used and SHOULD NOT be called:
`tell()`, `eof()`, `seek()`, `rewind()`, `write()` and `read()`.
If this is an issue for your use case and/or you want to access uploaded files,
it's highly recommended to use a buffered [request body](#request-body) or use the
[`RequestBodyBufferMiddleware`](#requestbodybuffermiddleware) instead.
The [ReactPHP `ReadableStreamInterface`](https://github.com/reactphp/stream#readablestreaminterface)
gives you access to the incoming request body as the individual chunks arrive:

```php
$http = new React\Http\HttpServer(
    new React\Http\Middleware\StreamingRequestMiddleware(),
    function (Psr\Http\Message\ServerRequestInterface $request) {
        $body = $request->getBody();
        assert($body instanceof Psr\Http\Message\StreamInterface);
        assert($body instanceof React\Stream\ReadableStreamInterface);

        return new React\Promise\Promise(function ($resolve, $reject) use ($body) {
            $bytes = 0;
            $body->on('data', function ($data) use (&$bytes) {
                $bytes += strlen($data);
            });

            $body->on('end', function () use ($resolve, &$bytes){
                $resolve(React\Http\Message\Response::plaintext(
                    "Received $bytes bytes\n"
                ));
            });

            // an error occures e.g. on invalid chunked encoded data or an unexpected 'end' event
            $body->on('error', function (Exception $e) use ($resolve, &$bytes) {
                $resolve(React\Http\Message\Response::plaintext(
                    "Encountered error after $bytes bytes: {$e->getMessage()}\n"
                )->withStatus(React\Http\Message\Response::STATUS_BAD_REQUEST));
            });
        });
    }
);
```

The above example simply counts the number of bytes received in the request body.
This can be used as a skeleton for buffering or processing the request body.

See also [streaming request server example](examples/63-server-streaming-request.php) for more details.

The `data` event will be emitted whenever new data is available on the request
body stream.
The server also automatically takes care of decoding any incoming requests using
`Transfer-Encoding: chunked` and will only emit the actual payload as data.

The `end` event will be emitted when the request body stream terminates
successfully, i.e. it was read until its expected end.

The `error` event will be emitted in case the request stream contains invalid
data for `Transfer-Encoding: chunked` or when the connection closes before
the complete request stream has been received.
The server will automatically stop reading from the connection and discard all
incoming data instead of closing it.
A response message can still be sent (unless the connection is already closed).

A `close` event will be emitted after an `error` or `end` event.

For more details about the request body stream, check out the documentation of
[ReactPHP `ReadableStreamInterface`](https://github.com/reactphp/stream#readablestreaminterface).

The `getSize(): ?int` method can be used to
get the size of the request body, similar to PHP's `$_SERVER['CONTENT_LENGTH']` variable.
This method returns the complete size of the request body measured in number
of bytes as defined by the message boundaries.
This value may be `0` if the request message does not contain a request body
(such as a simple `GET` request).
This method operates on the streaming request body, i.e. the request body size
may be unknown (`null`) when using `Transfer-Encoding: chunked` for HTTP/1.1 requests.

```php 
$http = new React\Http\HttpServer(
    new React\Http\Middleware\StreamingRequestMiddleware(),
    function (Psr\Http\Message\ServerRequestInterface $request) {
        $size = $request->getBody()->getSize();
        if ($size === null) {
            $body = "The request does not contain an explicit length. ";
            $body .= "This example does not accept chunked transfer encoding.\n";

            return React\Http\Message\Response::plaintext(
                $body
            )->withStatus(React\Http\Message\Response::STATUS_LENGTH_REQUIRED);
        }

        return React\Http\Message\Response::plaintext(
            "Request body size: " . $size . " bytes\n"
        );
    }
);
```

> Note: The `HttpServer` automatically takes care of handling requests with the
  additional `Expect: 100-continue` request header. When HTTP/1.1 clients want to
  send a bigger request body, they MAY send only the request headers with an
  additional `Expect: 100-continue` request header and wait before sending the actual
  (large) message body. In this case the server will automatically send an
  intermediary `HTTP/1.1 100 Continue` response to the client. This ensures you
  will receive the streaming request body without a delay as expected.

#### Request method

Note that the server supports *any* request method (including custom and non-
standard ones) and all request-target formats defined in the HTTP specs for each
respective method, including *normal* `origin-form` requests as well as
proxy requests in `absolute-form` and `authority-form`.
The `getUri(): UriInterface` method can be used to get the effective request
URI which provides you access to individiual URI components.
Note that (depending on the given `request-target`) certain URI components may
or may not be present, for example the `getPath(): string` method will return
an empty string for requests in `asterisk-form` or `authority-form`.
Its `getHost(): string` method will return the host as determined by the
effective request URI, which defaults to the local socket address if an HTTP/1.0
client did not specify one (i.e. no `Host` header).
Its `getScheme(): string` method will return `http` or `https` depending
on whether the request was made over a secure TLS connection to the target host.

The `Host` header value will be sanitized to match this host component plus the
port component only if it is non-standard for this URI scheme.

You can use `getMethod(): string` and `getRequestTarget(): string` to
check this is an accepted request and may want to reject other requests with
an appropriate error code, such as `400` (Bad Request) or `405` (Method Not
Allowed).

> The `CONNECT` method is useful in a tunneling setup (HTTPS proxy) and not
  something most HTTP servers would want to care about.
  Note that if you want to handle this method, the client MAY send a different
  request-target than the `Host` header value (such as removing default ports)
  and the request-target MUST take precendence when forwarding.

#### Cookie parameters

The `getCookieParams(): string[]` method can be used to
get all cookies sent with the current request.

```php 
$http = new React\Http\HttpServer(function (Psr\Http\Message\ServerRequestInterface $request) {
    $key = 'greeting';

    if (isset($request->getCookieParams()[$key])) {
        $body = "Your cookie value is: " . $request->getCookieParams()[$key] . "\n";

        return React\Http\Message\Response::plaintext(
            $body
        );
    }

    return React\Http\Message\Response::plaintext(
        "Your cookie has been set.\n"
    )->withHeader('Set-Cookie', $key . '=' . urlencode('Hello world!'));
});
```

The above example will try to set a cookie on first access and
will try to print the cookie value on all subsequent tries.
Note how the example uses the `urlencode()` function to encode
non-alphanumeric characters.
This encoding is also used internally when decoding the name and value of cookies
(which is in line with other implementations, such as PHP's cookie functions).

See also [cookie server example](examples/55-server-cookie-handling.php) for more details.

#### Invalid request

The `HttpServer` class supports both HTTP/1.1 and HTTP/1.0 request messages.
If a client sends an invalid request message, uses an invalid HTTP
protocol version or sends an invalid `Transfer-Encoding` request header value,
the server will automatically send a `400` (Bad Request) HTTP error response
to the client and close the connection.
On top of this, it will emit an `error` event that can be used for logging
purposes like this:

```php
$http->on('error', function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});
```

Note that the server will also emit an `error` event if you do not return a
valid response object from your request handler function. See also
[invalid response](#invalid-response) for more details.

### Server Response

The callback function passed to the constructor of the [`HttpServer`](#httpserver) is
responsible for processing the request and returning a response, which will be
delivered to the client.

This function MUST return an instance implementing
[PSR-7 `ResponseInterface`](https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface)
object or a 
[ReactPHP Promise](https://github.com/reactphp/promise)
which resolves with a [PSR-7 `ResponseInterface`](https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface) object.

This projects ships a [`Response` class](#response) which implements the
[PSR-7 `ResponseInterface`](https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface).
In its most simple form, you can use it like this:

```php 
$http = new React\Http\HttpServer(function (Psr\Http\Message\ServerRequestInterface $request) {
    return React\Http\Message\Response::plaintext(
        "Hello World!\n"
    );
});
```

We use this [`Response` class](#response) throughout our project examples, but
feel free to use any other implementation of the
[PSR-7 `ResponseInterface`](https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface).
See also the [`Response` class](#response) for more details.

#### Deferred response

The example above returns the response directly, because it needs
no time to be processed.
Using a database, the file system or long calculations 
(in fact every action that will take >=1ms) to create your
response, will slow down the server.
To prevent this you SHOULD use a
[ReactPHP Promise](https://github.com/reactphp/promise#reactpromise).
This example shows how such a long-term action could look like:

```php
$http = new React\Http\HttpServer(function (Psr\Http\Message\ServerRequestInterface $request) {
    $promise = new Promise(function ($resolve, $reject) {
        Loop::addTimer(1.5, function() use ($resolve) {
            $resolve();
        });
    });

    return $promise->then(function () { 
        return React\Http\Message\Response::plaintext(
            "Hello World!"
        );
    });
});
```

The above example will create a response after 1.5 second.
This example shows that you need a promise,
if your response needs time to created.
The `ReactPHP Promise` will resolve in a `Response` object when the request
body ends.
If the client closes the connection while the promise is still pending, the
promise will automatically be cancelled.
The promise cancellation handler can be used to clean up any pending resources
allocated in this case (if applicable).
If a promise is resolved after the client closes, it will simply be ignored.

#### Streaming outgoing response

The `Response` class in this project supports to add an instance which implements the
[ReactPHP `ReadableStreamInterface`](https://github.com/reactphp/stream#readablestreaminterface)
for the response body.
So you are able stream data directly into the response body.
Note that other implementations of the
[PSR-7 `ResponseInterface`](https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface)
may only support strings.

```php
$http = new React\Http\HttpServer(function (Psr\Http\Message\ServerRequestInterface $request) {
    $stream = new ThroughStream();

    // send some data every once in a while with periodic timer
    $timer = Loop::addPeriodicTimer(0.5, function () use ($stream) {
        $stream->write(microtime(true) . PHP_EOL);
    });

    // end stream after a few seconds
    $timeout = Loop::addTimer(5.0, function() use ($stream, $timer) {
        Loop::cancelTimer($timer);
        $stream->end();
    });

    // stop timer if stream is closed (such as when connection is closed)
    $stream->on('close', function () use ($timer, $timeout) {
        Loop::cancelTimer($timer);
        Loop::cancelTimer($timeout);
    });

    return new React\Http\Message\Response(
        React\Http\Message\Response::STATUS_OK,
        array(
            'Content-Type' => 'text/plain'
        ),
        $stream
    );
});
```

The above example will emit every 0.5 seconds the current Unix timestamp 
with microseconds as float to the client and will end after 5 seconds.
This is just a example you could use of the streaming,
you could also send a big amount of data via little chunks 
or use it for body data that needs to calculated.

If the request handler resolves with a response stream that is already closed,
it will simply send an empty response body.
If the client closes the connection while the stream is still open, the
response stream will automatically be closed.
If a promise is resolved with a streaming body after the client closes, the
response stream will automatically be closed.
The `close` event can be used to clean up any pending resources allocated
in this case (if applicable).

> Note that special care has to be taken if you use a body stream instance that
  implements ReactPHP's
  [`DuplexStreamInterface`](https://github.com/reactphp/stream#duplexstreaminterface)
  (such as the `ThroughStream` in the above example).
>
> For *most* cases, this will simply only consume its readable side and forward
  (send) any data that is emitted by the stream, thus entirely ignoring the
  writable side of the stream.
  If however this is either a `101` (Switching Protocols) response or a `2xx`
  (Successful) response to a `CONNECT` method, it will also *write* data to the
  writable side of the stream.
  This can be avoided by either rejecting all requests with the `CONNECT`
  method (which is what most *normal* origin HTTP servers would likely do) or
  or ensuring that only ever an instance of
  [ReactPHP's `ReadableStreamInterface`](https://github.com/reactphp/stream#readablestreaminterface)
  is used.
>
> The `101` (Switching Protocols) response code is useful for the more advanced
  `Upgrade` requests, such as upgrading to the WebSocket protocol or
  implementing custom protocol logic that is out of scope of the HTTP specs and
  this HTTP library.
  If you want to handle the `Upgrade: WebSocket` header, you will likely want
  to look into using [Ratchet](http://socketo.me/) instead.
  If you want to handle a custom protocol, you will likely want to look into the
  [HTTP specs](https://tools.ietf.org/html/rfc7230#section-6.7) and also see
  [examples #81 and #82](examples/) for more details.
  In particular, the `101` (Switching Protocols) response code MUST NOT be used
  unless you send an `Upgrade` response header value that is also present in
  the corresponding HTTP/1.1 `Upgrade` request header value.
  The server automatically takes care of sending a `Connection: upgrade`
  header value in this case, so you don't have to.
>
> The `CONNECT` method is useful in a tunneling setup (HTTPS proxy) and not
  something most origin HTTP servers would want to care about.
  The HTTP specs define an opaque "tunneling mode" for this method and make no
  use of the message body.
  For consistency reasons, this library uses a `DuplexStreamInterface` in the
  response body for tunneled application data.
  This implies that that a `2xx` (Successful) response to a `CONNECT` request
  can in fact use a streaming response body for the tunneled application data,
  so that any raw data the client sends over the connection will be piped
  through the writable stream for consumption.
  Note that while the HTTP specs make no use of the request body for `CONNECT`
  requests, one may still be present. Normal request body processing applies
  here and the connection will only turn to "tunneling mode" after the request
  body has been processed (which should be empty in most cases).
  See also [HTTP CONNECT server example](examples/72-server-http-connect-proxy.php) for more details.

#### Response length

If the response body size is known, a `Content-Length` response header will be
added automatically. This is the most common use case, for example when using
a `string` response body like this:

```php 
$http = new React\Http\HttpServer(function (Psr\Http\Message\ServerRequestInterface $request) {
    return React\Http\Message\Response::plaintext(
        "Hello World!\n"
    );
});
```

If the response body size is unknown, a `Content-Length` response header can not
be added automatically. When using a [streaming outgoing response](#streaming-outgoing-response)
without an explicit `Content-Length` response header, outgoing HTTP/1.1 response
messages will automatically use `Transfer-Encoding: chunked` while legacy HTTP/1.0
response messages will contain the plain response body. If you know the length
of your streaming response body, you MAY want to specify it explicitly like this:

```php
$http = new React\Http\HttpServer(function (Psr\Http\Message\ServerRequestInterface $request) {
    $stream = new ThroughStream();

    Loop::addTimer(2.0, function () use ($stream) {
        $stream->end("Hello World!\n");
    });

    return new React\Http\Message\Response(
        React\Http\Message\Response::STATUS_OK,
        array(
            'Content-Length' => '13',
            'Content-Type' => 'text/plain',
        ),
        $stream
    );
});
```

Any response to a `HEAD` request and any response with a `1xx` (Informational),
`204` (No Content) or `304` (Not Modified) status code will *not* include a
message body as per the HTTP specs.
This means that your callback does not have to take special care of this and any
response body will simply be ignored.

Similarly, any `2xx` (Successful) response to a `CONNECT` request, any response
with a `1xx` (Informational) or `204` (No Content) status code will *not*
include a `Content-Length` or `Transfer-Encoding` header as these do not apply
to these messages.
Note that a response to a `HEAD` request and any response with a `304` (Not
Modified) status code MAY include these headers even though
the message does not contain a response body, because these header would apply
to the message if the same request would have used an (unconditional) `GET`.

#### Invalid response

As stated above, each outgoing HTTP response is always represented by the
[PSR-7 `ResponseInterface`](https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface).
If your request handler function returns an invalid value or throws an
unhandled `Exception` or `Throwable`, the server will automatically send a `500`
(Internal Server Error) HTTP error response to the client.
On top of this, it will emit an `error` event that can be used for logging
purposes like this:

```php
$http->on('error', function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    if ($e->getPrevious() !== null) {
        echo 'Previous: ' . $e->getPrevious()->getMessage() . PHP_EOL;
    }
});
```

Note that the server will also emit an `error` event if the client sends an
invalid HTTP request that never reaches your request handler function. See
also [invalid request](#invalid-request) for more details.
Additionally, a [streaming incoming request](#streaming-incoming-request) body
can also emit an `error` event on the request body.

The server will only send a very generic `500` (Interval Server Error) HTTP
error response without any further details to the client if an unhandled
error occurs. While we understand this might make initial debugging harder,
it also means that the server does not leak any application details or stack
traces to the outside by default. It is usually recommended to catch any
`Exception` or `Throwable` within your request handler function or alternatively
use a [`middleware`](#middleware) to avoid this generic error handling and
create your own HTTP response message instead.

#### Default response headers

When a response is returned from the request handler function, it will be
processed by the [`HttpServer`](#httpserver) and then sent back to the client.

A `Server: ReactPHP/1` response header will be added automatically. You can add
a custom `Server` response header like this:

```php
$http = new React\Http\HttpServer(function (ServerRequestInterface $request) {
    return new React\Http\Message\Response(
        React\Http\Message\Response::STATUS_OK,
        array(
            'Server' => 'PHP/3'
        )
    );
});
```

If you do not want to send this `Sever` response header at all (such as when you
don't want to expose the underlying server software), you can use an empty
string value like this:

```php
$http = new React\Http\HttpServer(function (ServerRequestInterface $request) {
    return new React\Http\Message\Response(
        React\Http\Message\Response::STATUS_OK,
        array(
            'Server' => ''
        )
    );
});
```

A `Date` response header will be added automatically with the current system
date and time if none is given. You can add a custom `Date` response header
like this:

```php
$http = new React\Http\HttpServer(function (ServerRequestInterface $request) {
    return new React\Http\Message\Response(
        React\Http\Message\Response::STATUS_OK,
        array(
            'Date' => gmdate('D, d M Y H:i:s \G\M\T')
        )
    );
});
```

If you do not want to send this `Date` response header at all (such as when you
don't have an appropriate clock to rely on), you can use an empty string value
like this:

```php
$http = new React\Http\HttpServer(function (ServerRequestInterface $request) {
    return new React\Http\Message\Response(
        React\Http\Message\Response::STATUS_OK,
        array(
            'Date' => ''
        )
    );
});
```

The `HttpServer` class will automatically add the protocol version of the request,
so you don't have to. For instance, if the client sends the request using the
HTTP/1.1 protocol version, the response message will also use the same protocol
version, no matter what version is returned from the request handler function.

The server supports persistent connections. An appropriate `Connection: keep-alive`
or `Connection: close` response header will be added automatically, respecting the
matching request header value and HTTP default header values. The server is
responsible for handling the `Connection` response header, so you SHOULD NOT pass
this response header yourself, unless you explicitly want to override the user's
choice with a `Connection: close` response header.

### Middleware

As documented above, the [`HttpServer`](#httpserver) accepts a single request handler
argument that is responsible for processing an incoming HTTP request and then
creating and returning an outgoing HTTP response.

Many common use cases involve validating, processing, manipulating the incoming
HTTP request before passing it to the final business logic request handler.
As such, this project supports the concept of middleware request handlers.

#### Custom middleware

A middleware request handler is expected to adhere the following rules:

* It is a valid `callable`.
* It accepts an instance implementing
  [PSR-7 `ServerRequestInterface`](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface)
  as first argument and an optional `callable` as second argument.
* It returns either:
  * An instance implementing
    [PSR-7 `ResponseInterface`](https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface)
    for direct consumption.
  * Any promise which can be consumed by
    [`Promise\resolve()`](https://reactphp.org/promise/#resolve) resolving to a
    [PSR-7 `ResponseInterface`](https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface)
    for deferred consumption.
  * It MAY throw an `Exception` (or return a rejected promise) in order to
    signal an error condition and abort the chain.
* It calls `$next($request)` to continue processing the next middleware
  request handler or returns explicitly without calling `$next` to
  abort the chain.
  * The `$next` request handler (recursively) invokes the next request
    handler from the chain with the same logic as above and returns (or throws)
    as above.
  * The `$request` may be modified prior to calling `$next($request)` to
    change the incoming request the next middleware operates on.
  * The `$next` return value may be consumed to modify the outgoing response.
  * The `$next` request handler MAY be called more than once if you want to
    implement custom "retry" logic etc.

Note that this very simple definition allows you to use either anonymous
functions or any classes that use the magic `__invoke()` method.
This allows you to easily create custom middleware request handlers on the fly
or use a class based approach to ease using existing middleware implementations.

While this project does provide the means to *use* middleware implementations,
it does not aim to *define* how middleware implementations should look like.
We realize that there's a vivid ecosystem of middleware implementations and
ongoing effort to standardize interfaces between these with
[PSR-15](https://www.php-fig.org/psr/psr-15/) (HTTP Server Request Handlers)
and support this goal.
As such, this project only bundles a few middleware implementations that are
required to match PHP's request behavior (see below) and otherwise actively
encourages [Third-Party Middleware](#third-party-middleware) implementations.

In order to use middleware request handlers, simply pass a list of all
callables as defined above to the [`HttpServer`](#httpserver).
The following example adds a middleware request handler that adds the current time to the request as a 
header (`Request-Time`) and a final request handler that always returns a `200 OK` status code without a body: 

```php
$http = new React\Http\HttpServer(
    function (Psr\Http\Message\ServerRequestInterface $request, callable $next) {
        $request = $request->withHeader('Request-Time', time());
        return $next($request);
    },
    function (Psr\Http\Message\ServerRequestInterface $request) {
        return new React\Http\Message\Response(React\Http\Message\Response::STATUS_OK);
    }
);
```

> Note how the middleware request handler and the final request handler have a
  very simple (and similar) interface. The only difference is that the final
  request handler does not receive a `$next` handler.

Similarly, you can use the result of the `$next` middleware request handler
function to modify the outgoing response.
Note that as per the above documentation, the `$next` middleware request handler may return a
[PSR-7 `ResponseInterface`](https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface)
directly or one wrapped in a promise for deferred resolution.
In order to simplify handling both paths, you can simply wrap this in a
[`Promise\resolve()`](https://reactphp.org/promise/#resolve) call like this:

```php
$http = new React\Http\HttpServer(
    function (Psr\Http\Message\ServerRequestInterface $request, callable $next) {
        $promise = React\Promise\resolve($next($request));
        return $promise->then(function (ResponseInterface $response) {
            return $response->withHeader('Content-Type', 'text/html');
        });
    },
    function (Psr\Http\Message\ServerRequestInterface $request) {
        return new React\Http\Message\Response(React\Http\Message\Response::STATUS_OK);
    }
);
```

Note that the `$next` middleware request handler may also throw an
`Exception` (or return a rejected promise) as described above.
The previous example does not catch any exceptions and would thus signal an
error condition to the `HttpServer`.
Alternatively, you can also catch any `Exception` to implement custom error
handling logic (or logging etc.) by wrapping this in a
[`Promise`](https://reactphp.org/promise/#promise) like this:

```php
$http = new React\Http\HttpServer(
    function (Psr\Http\Message\ServerRequestInterface $request, callable $next) {
        $promise = new React\Promise\Promise(function ($resolve) use ($next, $request) {
            $resolve($next($request));
        });
        return $promise->then(null, function (Exception $e) {
            return React\Http\Message\Response::plaintext(
                'Internal error: ' . $e->getMessage() . "\n"
            )->withStatus(React\Http\Message\Response::STATUS_INTERNAL_SERVER_ERROR);
        });
    },
    function (Psr\Http\Message\ServerRequestInterface $request) {
        if (mt_rand(0, 1) === 1) {
            throw new RuntimeException('Database error');
        }
        return new React\Http\Message\Response(React\Http\Message\Response::STATUS_OK);
    }
);
```

#### Third-Party Middleware

While this project does provide the means to *use* middleware implementations
(see above), it does not aim to *define* how middleware implementations should
look like. We realize that there's a vivid ecosystem of middleware
implementations and ongoing effort to standardize interfaces between these with
[PSR-15](https://www.php-fig.org/psr/psr-15/) (HTTP Server Request Handlers)
and support this goal.
As such, this project only bundles a few middleware implementations that are
required to match PHP's request behavior (see
[middleware implementations](#reacthttpmiddleware)) and otherwise actively
encourages third-party middleware implementations.

While we would love to support PSR-15 directly in `react/http`, we understand
that this interface does not specifically target async APIs and as such does
not take advantage of promises for [deferred responses](#deferred-response).
The gist of this is that where PSR-15 enforces a
[PSR-7 `ResponseInterface`](https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface)
return value, we also accept a `PromiseInterface<ResponseInterface>`.
As such, we suggest using the external
[PSR-15 middleware adapter](https://github.com/friends-of-reactphp/http-middleware-psr15-adapter)
that uses on the fly monkey patching of these return values which makes using
most PSR-15 middleware possible with this package without any changes required.

Other than that, you can also use the above [middleware definition](#middleware)
to create custom middleware. A non-exhaustive list of third-party middleware can
be found at the [middleware wiki](https://github.com/reactphp/reactphp/wiki/Users#http-middleware).
If you build or know a custom middleware, make sure to let the world know and
feel free to add it to this list.

## API

### Browser

The `React\Http\Browser` is responsible for sending HTTP requests to your HTTP server
and keeps track of pending incoming HTTP responses.

```php
$browser = new React\Http\Browser();
```

This class takes two optional arguments for more advanced usage:

```php
// constructor signature as of v1.5.0
$browser = new React\Http\Browser(?ConnectorInterface $connector = null, ?LoopInterface $loop = null);

// legacy constructor signature before v1.5.0
$browser = new React\Http\Browser(?LoopInterface $loop = null, ?ConnectorInterface $connector = null);
```

If you need custom connector settings (DNS resolution, TLS parameters, timeouts,
proxy servers etc.), you can explicitly pass a custom instance of the
[`ConnectorInterface`](https://github.com/reactphp/socket#connectorinterface):

```php
$connector = new React\Socket\Connector(array(
    'dns' => '127.0.0.1',
    'tcp' => array(
        'bindto' => '192.168.10.1:0'
    ),
    'tls' => array(
        'verify_peer' => false,
        'verify_peer_name' => false
    )
));

$browser = new React\Http\Browser($connector);
```

This class takes an optional `LoopInterface|null $loop` parameter that can be used to
pass the event loop instance to use for this object. You can use a `null` value
here in order to use the [default loop](https://github.com/reactphp/event-loop#loop).
This value SHOULD NOT be given unless you're sure you want to explicitly use a
given event loop instance.

> Note that the browser class is final and shouldn't be extended, it is likely to be marked final in a future release.

#### get()

The `get(string $url, array $headers = array()): PromiseInterface<ResponseInterface>` method can be used to
send an HTTP GET request.

```php
$browser->get($url)->then(function (Psr\Http\Message\ResponseInterface $response) {
    var_dump((string)$response->getBody());
}, function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});
```

See also [GET request client example](examples/01-client-get-request.php).

#### post()

The `post(string $url, array $headers = array(), string|ReadableStreamInterface $body = ''): PromiseInterface<ResponseInterface>` method can be used to
send an HTTP POST request.

```php
$browser->post(
    $url,
    [
        'Content-Type' => 'application/json'
    ],
    json_encode($data)
)->then(function (Psr\Http\Message\ResponseInterface $response) {
    var_dump(json_decode((string)$response->getBody()));
}, function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});
```

See also [POST JSON client example](examples/04-client-post-json.php).

This method is also commonly used to submit HTML form data:

```php
$data = [
    'user' => 'Alice',
    'password' => 'secret'
];

$browser->post(
    $url,
    [
        'Content-Type' => 'application/x-www-form-urlencoded'
    ],
    http_build_query($data)
);
```

This method will automatically add a matching `Content-Length` request
header if the outgoing request body is a `string`. If you're using a
streaming request body (`ReadableStreamInterface`), it will default to
using `Transfer-Encoding: chunked` or you have to explicitly pass in a
matching `Content-Length` request header like so:

```php
$body = new React\Stream\ThroughStream();
Loop::addTimer(1.0, function () use ($body) {
    $body->end("hello world");
});

$browser->post($url, array('Content-Length' => '11'), $body);
```

#### head()

The `head(string $url, array $headers = array()): PromiseInterface<ResponseInterface>` method can be used to
send an HTTP HEAD request.

```php
$browser->head($url)->then(function (Psr\Http\Message\ResponseInterface $response) {
    var_dump($response->getHeaders());
}, function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});
```

#### patch()

The `patch(string $url, array $headers = array(), string|ReadableStreamInterface $body = ''): PromiseInterface<ResponseInterface>` method can be used to
send an HTTP PATCH request.

```php
$browser->patch(
    $url,
    [
        'Content-Type' => 'application/json'
    ],
    json_encode($data)
)->then(function (Psr\Http\Message\ResponseInterface $response) {
    var_dump(json_decode((string)$response->getBody()));
}, function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});
```

This method will automatically add a matching `Content-Length` request
header if the outgoing request body is a `string`. If you're using a
streaming request body (`ReadableStreamInterface`), it will default to
using `Transfer-Encoding: chunked` or you have to explicitly pass in a
matching `Content-Length` request header like so:

```php
$body = new React\Stream\ThroughStream();
Loop::addTimer(1.0, function () use ($body) {
    $body->end("hello world");
});

$browser->patch($url, array('Content-Length' => '11'), $body);
```

#### put()

The `put(string $url, array $headers = array(), string|ReadableStreamInterface $body = ''): PromiseInterface<ResponseInterface>` method can be used to
send an HTTP PUT request.

```php
$browser->put(
    $url,
    [
        'Content-Type' => 'text/xml'
    ],
    $xml->asXML()
)->then(function (Psr\Http\Message\ResponseInterface $response) {
    var_dump((string)$response->getBody());
}, function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});
```

See also [PUT XML client example](examples/05-client-put-xml.php).

This method will automatically add a matching `Content-Length` request
header if the outgoing request body is a `string`. If you're using a
streaming request body (`ReadableStreamInterface`), it will default to
using `Transfer-Encoding: chunked` or you have to explicitly pass in a
matching `Content-Length` request header like so:

```php
$body = new React\Stream\ThroughStream();
Loop::addTimer(1.0, function () use ($body) {
    $body->end("hello world");
});

$browser->put($url, array('Content-Length' => '11'), $body);
```

#### delete()

The `delete(string $url, array $headers = array(), string|ReadableStreamInterface $body = ''): PromiseInterface<ResponseInterface>` method can be used to
send an HTTP DELETE request.

```php
$browser->delete($url)->then(function (Psr\Http\Message\ResponseInterface $response) {
    var_dump((string)$response->getBody());
}, function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});
```

#### request()

The `request(string $method, string $url, array $headers = array(), string|ReadableStreamInterface $body = ''): PromiseInterface<ResponseInterface>` method can be used to
send an arbitrary HTTP request.

The preferred way to send an HTTP request is by using the above
[request methods](#request-methods), for example the [`get()`](#get)
method to send an HTTP `GET` request.

As an alternative, if you want to use a custom HTTP request method, you
can use this method:

```php
$browser->request('OPTIONS', $url)->then(function (Psr\Http\Message\ResponseInterface $response) {
    var_dump((string)$response->getBody());
}, function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});
```

This method will automatically add a matching `Content-Length` request
header if the size of the outgoing request body is known and non-empty.
For an empty request body, if will only include a `Content-Length: 0`
request header if the request method usually expects a request body (only
applies to `POST`, `PUT` and `PATCH`).

If you're using a streaming request body (`ReadableStreamInterface`), it
will default to using `Transfer-Encoding: chunked` or you have to
explicitly pass in a matching `Content-Length` request header like so:

```php
$body = new React\Stream\ThroughStream();
Loop::addTimer(1.0, function () use ($body) {
    $body->end("hello world");
});

$browser->request('POST', $url, array('Content-Length' => '11'), $body);
```

#### requestStreaming()

The `requestStreaming(string $method, string $url, array $headers = array(), string|ReadableStreamInterface $body = ''): PromiseInterface<ResponseInterface>` method can be used to
send an arbitrary HTTP request and receive a streaming response without buffering the response body.

The preferred way to send an HTTP request is by using the above
[request methods](#request-methods), for example the [`get()`](#get)
method to send an HTTP `GET` request. Each of these methods will buffer
the whole response body in memory by default. This is easy to get started
and works reasonably well for smaller responses.

In some situations, it's a better idea to use a streaming approach, where
only small chunks have to be kept in memory. You can use this method to
send an arbitrary HTTP request and receive a streaming response. It uses
the same HTTP message API, but does not buffer the response body in
memory. It only processes the response body in small chunks as data is
received and forwards this data through [ReactPHP's Stream API](https://github.com/reactphp/stream).
This works for (any number of) responses of arbitrary sizes.

```php
$browser->requestStreaming('GET', $url)->then(function (Psr\Http\Message\ResponseInterface $response) {
    $body = $response->getBody();
    assert($body instanceof Psr\Http\Message\StreamInterface);
    assert($body instanceof React\Stream\ReadableStreamInterface);

    $body->on('data', function ($chunk) {
        echo $chunk;
    });

    $body->on('error', function (Exception $e) {
        echo 'Error: ' . $e->getMessage() . PHP_EOL;
    });

    $body->on('close', function () {
        echo '[DONE]' . PHP_EOL;
    });
}, function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});
```

See also [ReactPHP's `ReadableStreamInterface`](https://github.com/reactphp/stream#readablestreaminterface)
and the [streaming response](#streaming-response) for more details,
examples and possible use-cases.

This method will automatically add a matching `Content-Length` request
header if the size of the outgoing request body is known and non-empty.
For an empty request body, if will only include a `Content-Length: 0`
request header if the request method usually expects a request body (only
applies to `POST`, `PUT` and `PATCH`).

If you're using a streaming request body (`ReadableStreamInterface`), it
will default to using `Transfer-Encoding: chunked` or you have to
explicitly pass in a matching `Content-Length` request header like so:

```php
$body = new React\Stream\ThroughStream();
Loop::addTimer(1.0, function () use ($body) {
    $body->end("hello world");
});

$browser->requestStreaming('POST', $url, array('Content-Length' => '11'), $body);
```

#### withTimeout()

The `withTimeout(bool|number $timeout): Browser` method can be used to
change the maximum timeout used for waiting for pending requests.

You can pass in the number of seconds to use as a new timeout value:

```php
$browser = $browser->withTimeout(10.0);
```

You can pass in a bool `false` to disable any timeouts. In this case,
requests can stay pending forever:

```php
$browser = $browser->withTimeout(false);
```

You can pass in a bool `true` to re-enable default timeout handling. This
will respects PHP's `default_socket_timeout` setting (default 60s):

```php
$browser = $browser->withTimeout(true);
```

See also [timeouts](#timeouts) for more details about timeout handling.

Notice that the [`Browser`](#browser) is an immutable object, i.e. this
method actually returns a *new* [`Browser`](#browser) instance with the
given timeout value applied.

#### withFollowRedirects()

The `withFollowRedirects(bool|int $followRedirects): Browser` method can be used to
change how HTTP redirects will be followed.

You can pass in the maximum number of redirects to follow:

```php
$browser = $browser->withFollowRedirects(5);
```

The request will automatically be rejected when the number of redirects
is exceeded. You can pass in a `0` to reject the request for any
redirects encountered:

```php
$browser = $browser->withFollowRedirects(0);

$browser->get($url)->then(function (Psr\Http\Message\ResponseInterface $response) {
    // only non-redirected responses will now end up here
    var_dump($response->getHeaders());
}, function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});
```

You can pass in a bool `false` to disable following any redirects. In
this case, requests will resolve with the redirection response instead
of following the `Location` response header:

```php
$browser = $browser->withFollowRedirects(false);

$browser->get($url)->then(function (Psr\Http\Message\ResponseInterface $response) {
    // any redirects will now end up here
    var_dump($response->getHeaderLine('Location'));
}, function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});
```

You can pass in a bool `true` to re-enable default redirect handling.
This defaults to following a maximum of 10 redirects:

```php
$browser = $browser->withFollowRedirects(true);
```

See also [redirects](#redirects) for more details about redirect handling.

Notice that the [`Browser`](#browser) is an immutable object, i.e. this
method actually returns a *new* [`Browser`](#browser) instance with the
given redirect setting applied.

#### withRejectErrorResponse()

The `withRejectErrorResponse(bool $obeySuccessCode): Browser` method can be used to
change whether non-successful HTTP response status codes (4xx and 5xx) will be rejected.

You can pass in a bool `false` to disable rejecting incoming responses
that use a 4xx or 5xx response status code. In this case, requests will
resolve with the response message indicating an error condition:

```php
$browser = $browser->withRejectErrorResponse(false);

$browser->get($url)->then(function (Psr\Http\Message\ResponseInterface $response) {
    // any HTTP response will now end up here
    var_dump($response->getStatusCode(), $response->getReasonPhrase());
}, function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});
```

You can pass in a bool `true` to re-enable default status code handling.
This defaults to rejecting any response status codes in the 4xx or 5xx
range with a [`ResponseException`](#responseexception):

```php
$browser = $browser->withRejectErrorResponse(true);

$browser->get($url)->then(function (Psr\Http\Message\ResponseInterface $response) {
    // any successful HTTP response will now end up here
    var_dump($response->getStatusCode(), $response->getReasonPhrase());
}, function (Exception $e) {
    if ($e instanceof React\Http\Message\ResponseException) {
        // any HTTP response error message will now end up here
        $response = $e->getResponse();
        var_dump($response->getStatusCode(), $response->getReasonPhrase());
    } else {
        echo 'Error: ' . $e->getMessage() . PHP_EOL;
    }
});
```

Notice that the [`Browser`](#browser) is an immutable object, i.e. this
method actually returns a *new* [`Browser`](#browser) instance with the
given setting applied.

#### withBase()

The `withBase(string|null $baseUrl): Browser` method can be used to
change the base URL used to resolve relative URLs to.

If you configure a base URL, any requests to relative URLs will be
processed by first resolving this relative to the given absolute base
URL. This supports resolving relative path references (like `../` etc.).
This is particularly useful for (RESTful) API calls where all endpoints
(URLs) are located under a common base URL.

```php
$browser = $browser->withBase('http://api.example.com/v3/');

// will request http://api.example.com/v3/users
$browser->get('users')->then(…);
```

You can pass in a `null` base URL to return a new instance that does not
use a base URL:

```php
$browser = $browser->withBase(null);
```

Accordingly, any requests using relative URLs to a browser that does not
use a base URL can not be completed and will be rejected without sending
a request.

This method will throw an `InvalidArgumentException` if the given
`$baseUrl` argument is not a valid URL.

Notice that the [`Browser`](#browser) is an immutable object, i.e. the `withBase()` method
actually returns a *new* [`Browser`](#browser) instance with the given base URL applied.

#### withProtocolVersion()

The `withProtocolVersion(string $protocolVersion): Browser` method can be used to
change the HTTP protocol version that will be used for all subsequent requests.

All the above [request methods](#request-methods) default to sending
requests as HTTP/1.1. This is the preferred HTTP protocol version which
also provides decent backwards-compatibility with legacy HTTP/1.0
servers. As such, there should rarely be a need to explicitly change this
protocol version.

If you want to explicitly use the legacy HTTP/1.0 protocol version, you
can use this method:

```php
$browser = $browser->withProtocolVersion('1.0');

$browser->get($url)->then(…);
```

Notice that the [`Browser`](#browser) is an immutable object, i.e. this
method actually returns a *new* [`Browser`](#browser) instance with the
new protocol version applied.

#### withResponseBuffer()

The `withResponseBuffer(int $maximumSize): Browser` method can be used to
change the maximum size for buffering a response body.

The preferred way to send an HTTP request is by using the above
[request methods](#request-methods), for example the [`get()`](#get)
method to send an HTTP `GET` request. Each of these methods will buffer
the whole response body in memory by default. This is easy to get started
and works reasonably well for smaller responses.

By default, the response body buffer will be limited to 16 MiB. If the
response body exceeds this maximum size, the request will be rejected.

You can pass in the maximum number of bytes to buffer:

```php
$browser = $browser->withResponseBuffer(1024 * 1024);

$browser->get($url)->then(function (Psr\Http\Message\ResponseInterface $response) {
    // response body will not exceed 1 MiB
    var_dump($response->getHeaders(), (string) $response->getBody());
}, function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});
```

Note that the response body buffer has to be kept in memory for each
pending request until its transfer is completed and it will only be freed
after a pending request is fulfilled. As such, increasing this maximum
buffer size to allow larger response bodies is usually not recommended.
Instead, you can use the [`requestStreaming()` method](#requeststreaming)
to receive responses with arbitrary sizes without buffering. Accordingly,
this maximum buffer size setting has no effect on streaming responses.

Notice that the [`Browser`](#browser) is an immutable object, i.e. this
method actually returns a *new* [`Browser`](#browser) instance with the
given setting applied.

#### withHeader()

The `withHeader(string $header, string $value): Browser` method can be used to
add a request header for all following requests.

```php
$browser = $browser->withHeader('User-Agent', 'ACME');

$browser->get($url)->then(…);
```

Note that the new header will overwrite any headers previously set with
the same name (case-insensitive). Following requests will use these headers
by default unless they are explicitly set for any requests.

#### withoutHeader()

The `withoutHeader(string $header): Browser` method can be used to
remove any default request headers previously set via
the [`withHeader()` method](#withheader).

```php
$browser = $browser->withoutHeader('User-Agent');

$browser->get($url)->then(…);
```

Note that this method only affects the headers which were set with the
method `withHeader(string $header, string $value): Browser`

### React\Http\Message

#### Response

The `React\Http\Message\Response` class can be used to
represent an outgoing server response message.

```php
$response = new React\Http\Message\Response(
    React\Http\Message\Response::STATUS_OK,
    array(
        'Content-Type' => 'text/html'
    ),
    "<html>Hello world!</html>\n"
);
```

This class implements the
[PSR-7 `ResponseInterface`](https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface)
which in turn extends the
[PSR-7 `MessageInterface`](https://www.php-fig.org/psr/psr-7/#31-psrhttpmessagemessageinterface).

On top of this, this class implements the
[PSR-7 Message Util `StatusCodeInterface`](https://github.com/php-fig/http-message-util/blob/master/src/StatusCodeInterface.php)
which means that most common HTTP status codes are available as class
constants with the `STATUS_*` prefix. For instance, the `200 OK` and
`404 Not Found` status codes can used as `Response::STATUS_OK` and
`Response::STATUS_NOT_FOUND` respectively.

> Internally, this implementation builds on top of a base class which is
  considered an implementation detail that may change in the future.

##### html()

The static `html(string $html): Response` method can be used to
create an HTML response.

```php
$html = <<<HTML
<!doctype html>
<html>
<body>Hello wörld!</body>
</html>

HTML;

$response = React\Http\Message\Response::html($html);
```

This is a convenient shortcut method that returns the equivalent of this:

```
$response = new React\Http\Message\Response(
    React\Http\Message\Response::STATUS_OK,
    [
        'Content-Type' => 'text/html; charset=utf-8'
    ],
    $html
);
```

This method always returns a response with a `200 OK` status code and
the appropriate `Content-Type` response header for the given HTTP source
string encoded in UTF-8 (Unicode). It's generally recommended to end the
given plaintext string with a trailing newline.

If you want to use a different status code or custom HTTP response
headers, you can manipulate the returned response object using the
provided PSR-7 methods or directly instantiate a custom HTTP response
object using the `Response` constructor:

```php
$response = React\Http\Message\Response::html(
    "<h1>Error</h1>\n<p>Invalid user name given.</p>\n"
)->withStatus(React\Http\Message\Response::STATUS_BAD_REQUEST);
```

##### json()

The static `json(mixed $data): Response` method can be used to
create a JSON response.

```php
$response = React\Http\Message\Response::json(['name' => 'Alice']);
```

This is a convenient shortcut method that returns the equivalent of this:

```
$response = new React\Http\Message\Response(
    React\Http\Message\Response::STATUS_OK,
    [
        'Content-Type' => 'application/json'
    ],
    json_encode(
        ['name' => 'Alice'],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION
    ) . "\n"
);
```

This method always returns a response with a `200 OK` status code and
the appropriate `Content-Type` response header for the given structured
data encoded as a JSON text.

The given structured data will be encoded as a JSON text. Any `string`
values in the data must be encoded in UTF-8 (Unicode). If the encoding
fails, this method will throw an `InvalidArgumentException`.

By default, the given structured data will be encoded with the flags as
shown above. This includes pretty printing (PHP 5.4+) and preserving
zero fractions for `float` values (PHP 5.6.6+) to ease debugging. It is
assumed any additional data overhead is usually compensated by using HTTP
response compression.

If you want to use a different status code or custom HTTP response
headers, you can manipulate the returned response object using the
provided PSR-7 methods or directly instantiate a custom HTTP response
object using the `Response` constructor:

```php
$response = React\Http\Message\Response::json(
    ['error' => 'Invalid user name given']
)->withStatus(React\Http\Message\Response::STATUS_BAD_REQUEST);
```

##### plaintext()

The static `plaintext(string $text): Response` method can be used to
create a plaintext response.

```php
$response = React\Http\Message\Response::plaintext("Hello wörld!\n");
```

This is a convenient shortcut method that returns the equivalent of this:

```
$response = new React\Http\Message\Response(
    React\Http\Message\Response::STATUS_OK,
    [
        'Content-Type' => 'text/plain; charset=utf-8'
    ],
    "Hello wörld!\n"
);
```

This method always returns a response with a `200 OK` status code and
the appropriate `Content-Type` response header for the given plaintext
string encoded in UTF-8 (Unicode). It's generally recommended to end the
given plaintext string with a trailing newline.

If you want to use a different status code or custom HTTP response
headers, you can manipulate the returned response object using the
provided PSR-7 methods or directly instantiate a custom HTTP response
object using the `Response` constructor:

```php
$response = React\Http\Message\Response::plaintext(
    "Error: Invalid user name given.\n"
)->withStatus(React\Http\Message\Response::STATUS_BAD_REQUEST);
```

##### xml()

The static `xml(string $xml): Response` method can be used to
create an XML response.

```php
$xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<body>
    <greeting>Hello wörld!</greeting>
</body>

XML;

$response = React\Http\Message\Response::xml($xml);
```

This is a convenient shortcut method that returns the equivalent of this:

```
$response = new React\Http\Message\Response(
    React\Http\Message\Response::STATUS_OK,
    [
        'Content-Type' => 'application/xml'
    ],
    $xml
);
```

This method always returns a response with a `200 OK` status code and
the appropriate `Content-Type` response header for the given XML source
string. It's generally recommended to use UTF-8 (Unicode) and specify
this as part of the leading XML declaration and to end the given XML
source string with a trailing newline.

If you want to use a different status code or custom HTTP response
headers, you can manipulate the returned response object using the
provided PSR-7 methods or directly instantiate a custom HTTP response
object using the `Response` constructor:

```php
$response = React\Http\Message\Response::xml(
    "<error><message>Invalid user name given.</message></error>\n"
)->withStatus(React\Http\Message\Response::STATUS_BAD_REQUEST);
```

#### Request

The `React\Http\Message\Request` class can be used to
respresent an outgoing HTTP request message.

This class implements the
[PSR-7 `RequestInterface`](https://www.php-fig.org/psr/psr-7/#32-psrhttpmessagerequestinterface)
which extends the
[PSR-7 `MessageInterface`](https://www.php-fig.org/psr/psr-7/#31-psrhttpmessagemessageinterface).

This is mostly used internally to represent each outgoing HTTP request
message for the HTTP client implementation. Likewise, you can also use this
class with other HTTP client implementations and for tests.

> Internally, this implementation builds on top of a base class which is
  considered an implementation detail that may change in the future.

#### ServerRequest

The `React\Http\Message\ServerRequest` class can be used to
respresent an incoming server request message.

This class implements the
[PSR-7 `ServerRequestInterface`](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface)
which extends the
[PSR-7 `RequestInterface`](https://www.php-fig.org/psr/psr-7/#32-psrhttpmessagerequestinterface)
which in turn extends the
[PSR-7 `MessageInterface`](https://www.php-fig.org/psr/psr-7/#31-psrhttpmessagemessageinterface).

This is mostly used internally to represent each incoming request message.
Likewise, you can also use this class in test cases to test how your web
application reacts to certain HTTP requests.

> Internally, this implementation builds on top of a base class which is
  considered an implementation detail that may change in the future.

#### Uri

The `React\Http\Message\Uri` class can be used to
respresent a URI (or URL).

This class implements the
[PSR-7 `UriInterface`](https://www.php-fig.org/psr/psr-7/#35-psrhttpmessageuriinterface).

This is mostly used internally to represent the URI of each HTTP request
message for our HTTP client and server implementations. Likewise, you may
also use this class with other HTTP implementations and for tests.

#### ResponseException

The `React\Http\Message\ResponseException` is an `Exception` sub-class that will be used to reject
a request promise if the remote server returns a non-success status code
(anything but 2xx or 3xx).
You can control this behavior via the [`withRejectErrorResponse()` method](#withrejecterrorresponse).

The `getCode(): int` method can be used to
return the HTTP response status code.

The `getResponse(): ResponseInterface` method can be used to
access its underlying response object.

### React\Http\Middleware

#### StreamingRequestMiddleware

The `React\Http\Middleware\StreamingRequestMiddleware` can be used to
process incoming requests with a streaming request body (without buffering).

This allows you to process requests of any size without buffering the request
body in memory. Instead, it will represent the request body as a
[`ReadableStreamInterface`](https://github.com/reactphp/stream#readablestreaminterface)
that emit chunks of incoming data as it is received:

```php
$http = new React\Http\HttpServer(
    new React\Http\Middleware\StreamingRequestMiddleware(),
    function (Psr\Http\Message\ServerRequestInterface $request) {
        $body = $request->getBody();
        assert($body instanceof Psr\Http\Message\StreamInterface);
        assert($body instanceof React\Stream\ReadableStreamInterface);

        return new React\Promise\Promise(function ($resolve) use ($body) {
            $bytes = 0;
            $body->on('data', function ($chunk) use (&$bytes) {
                $bytes += \count($chunk);
            });
            $body->on('close', function () use (&$bytes, $resolve) {
                $resolve(new React\Http\Message\Response(
                    React\Http\Message\Response::STATUS_OK,
                    [],
                    "Received $bytes bytes\n"
                ));
            });
        });
    }
);
```

See also [streaming incoming request](#streaming-incoming-request)
for more details.

Additionally, this middleware can be used in combination with the
[`LimitConcurrentRequestsMiddleware`](#limitconcurrentrequestsmiddleware) and
[`RequestBodyBufferMiddleware`](#requestbodybuffermiddleware) (see below)
to explicitly configure the total number of requests that can be handled at
once:

```php
$http = new React\Http\HttpServer(
    new React\Http\Middleware\StreamingRequestMiddleware(),
    new React\Http\Middleware\LimitConcurrentRequestsMiddleware(100), // 100 concurrent buffering handlers
    new React\Http\Middleware\RequestBodyBufferMiddleware(2 * 1024 * 1024), // 2 MiB per request
    new React\Http\Middleware\RequestBodyParserMiddleware(),
    $handler
);
```

> Internally, this class is used as a "marker" to not trigger the default
  request buffering behavior in the `HttpServer`. It does not implement any logic
  on its own.

#### LimitConcurrentRequestsMiddleware

The `React\Http\Middleware\LimitConcurrentRequestsMiddleware` can be used to
limit how many next handlers can be executed concurrently.

If this middleware is invoked, it will check if the number of pending
handlers is below the allowed limit and then simply invoke the next handler
and it will return whatever the next handler returns (or throws).

If the number of pending handlers exceeds the allowed limit, the request will
be queued (and its streaming body will be paused) and it will return a pending
promise.
Once a pending handler returns (or throws), it will pick the oldest request
from this queue and invokes the next handler (and its streaming body will be
resumed).

The following example shows how this middleware can be used to ensure no more
than 10 handlers will be invoked at once:

```php
$http = new React\Http\HttpServer(
    new React\Http\Middleware\LimitConcurrentRequestsMiddleware(10),
    $handler
);
```

Similarly, this middleware is often used in combination with the
[`RequestBodyBufferMiddleware`](#requestbodybuffermiddleware) (see below)
to limit the total number of requests that can be buffered at once:

```php
$http = new React\Http\HttpServer(
    new React\Http\Middleware\StreamingRequestMiddleware(),
    new React\Http\Middleware\LimitConcurrentRequestsMiddleware(100), // 100 concurrent buffering handlers
    new React\Http\Middleware\RequestBodyBufferMiddleware(2 * 1024 * 1024), // 2 MiB per request
    new React\Http\Middleware\RequestBodyParserMiddleware(),
    $handler
);
```

More sophisticated examples include limiting the total number of requests
that can be buffered at once and then ensure the actual request handler only
processes one request after another without any concurrency:

```php
$http = new React\Http\HttpServer(
    new React\Http\Middleware\StreamingRequestMiddleware(),
    new React\Http\Middleware\LimitConcurrentRequestsMiddleware(100), // 100 concurrent buffering handlers
    new React\Http\Middleware\RequestBodyBufferMiddleware(2 * 1024 * 1024), // 2 MiB per request
    new React\Http\Middleware\RequestBodyParserMiddleware(),
    new React\Http\Middleware\LimitConcurrentRequestsMiddleware(1), // only execute 1 handler (no concurrency)
    $handler
);
```

#### RequestBodyBufferMiddleware

One of the built-in middleware is the `React\Http\Middleware\RequestBodyBufferMiddleware` which
can be used to buffer the whole incoming request body in memory.
This can be useful if full PSR-7 compatibility is needed for the request handler
and the default streaming request body handling is not needed.
The constructor accepts one optional argument, the maximum request body size.
When one isn't provided it will use `post_max_size` (default 8 MiB) from PHP's
configuration.
(Note that the value from your matching SAPI will be used, which is the CLI
configuration in most cases.)

Any incoming request that has a request body that exceeds this limit will be
accepted, but its request body will be discarded (empty request body).
This is done in order to avoid having to keep an incoming request with an
excessive size (for example, think of a 2 GB file upload) in memory.
This allows the next middleware handler to still handle this request, but it
will see an empty request body.
This is similar to PHP's default behavior, where the body will not be parsed
if this limit is exceeded. However, unlike PHP's default behavior, the raw
request body is not available via `php://input`.

The `RequestBodyBufferMiddleware` will buffer requests with bodies of known size 
(i.e. with `Content-Length` header specified) as well as requests with bodies of 
unknown size (i.e. with `Transfer-Encoding: chunked` header).

All requests will be buffered in memory until the request body end has
been reached and then call the next middleware handler with the complete,
buffered request.
Similarly, this will immediately invoke the next middleware handler for requests
that have an empty request body (such as a simple `GET` request) and requests
that are already buffered (such as due to another middleware).

Note that the given buffer size limit is applied to each request individually.
This means that if you allow a 2 MiB limit and then receive 1000 concurrent
requests, up to 2000 MiB may be allocated for these buffers alone.
As such, it's highly recommended to use this along with the
[`LimitConcurrentRequestsMiddleware`](#limitconcurrentrequestsmiddleware) (see above) to limit
the total number of concurrent requests.

Usage:

```php
$http = new React\Http\HttpServer(
    new React\Http\Middleware\StreamingRequestMiddleware(),
    new React\Http\Middleware\LimitConcurrentRequestsMiddleware(100), // 100 concurrent buffering handlers
    new React\Http\Middleware\RequestBodyBufferMiddleware(16 * 1024 * 1024), // 16 MiB
    function (Psr\Http\Message\ServerRequestInterface $request) {
        // The body from $request->getBody() is now fully available without the need to stream it 
        return new React\Http\Message\Response(React\Http\Message\Response::STATUS_OK);
    },
);
```

#### RequestBodyParserMiddleware

The `React\Http\Middleware\RequestBodyParserMiddleware` takes a fully buffered request body
(generally from [`RequestBodyBufferMiddleware`](#requestbodybuffermiddleware)), 
and parses the form values and file uploads from the incoming HTTP request body.

This middleware handler takes care of applying values from HTTP
requests that use `Content-Type: application/x-www-form-urlencoded` or
`Content-Type: multipart/form-data` to resemble PHP's default superglobals
`$_POST` and `$_FILES`.
Instead of relying on these superglobals, you can use the
`$request->getParsedBody()` and `$request->getUploadedFiles()` methods
as defined by PSR-7.

Accordingly, each file upload will be represented as instance implementing the
[PSR-7 `UploadedFileInterface`](https://www.php-fig.org/psr/psr-7/#36-psrhttpmessageuploadedfileinterface).
Due to its blocking nature, the `moveTo()` method is not available and throws
a `RuntimeException` instead.
You can use `$contents = (string)$file->getStream();` to access the file
contents and persist this to your favorite data store.

```php
$handler = function (Psr\Http\Message\ServerRequestInterface $request) {
    // If any, parsed form fields are now available from $request->getParsedBody()
    $body = $request->getParsedBody();
    $name = isset($body['name']) ? $body['name'] : 'unnamed';

    $files = $request->getUploadedFiles();
    $avatar = isset($files['avatar']) ? $files['avatar'] : null;
    if ($avatar instanceof Psr\Http\Message\UploadedFileInterface) {
        if ($avatar->getError() === UPLOAD_ERR_OK) {
            $uploaded = $avatar->getSize() . ' bytes';
        } elseif ($avatar->getError() === UPLOAD_ERR_INI_SIZE) {
            $uploaded = 'file too large';
        } else {
            $uploaded = 'with error';
        }
    } else {
        $uploaded = 'nothing';
    }

    return new React\Http\Message\Response(
        React\Http\Message\Response::STATUS_OK,
        array(
            'Content-Type' => 'text/plain'
        ),
        $name . ' uploaded ' . $uploaded
    );
};

$http = new React\Http\HttpServer(
    new React\Http\Middleware\StreamingRequestMiddleware(),
    new React\Http\Middleware\LimitConcurrentRequestsMiddleware(100), // 100 concurrent buffering handlers
    new React\Http\Middleware\RequestBodyBufferMiddleware(16 * 1024 * 1024), // 16 MiB
    new React\Http\Middleware\RequestBodyParserMiddleware(),
    $handler
);
```

See also [form upload server example](examples/62-server-form-upload.php) for more details.

By default, this middleware respects the
[`upload_max_filesize`](https://www.php.net/manual/en/ini.core.php#ini.upload-max-filesize)
(default `2M`) ini setting.
Files that exceed this limit will be rejected with an `UPLOAD_ERR_INI_SIZE` error.
You can control the maximum filesize for each individual file upload by
explicitly passing the maximum filesize in bytes as the first parameter to the
constructor like this:

```php
new React\Http\Middleware\RequestBodyParserMiddleware(8 * 1024 * 1024); // 8 MiB limit per file
```

By default, this middleware respects the
[`file_uploads`](https://www.php.net/manual/en/ini.core.php#ini.file-uploads)
(default `1`) and
[`max_file_uploads`](https://www.php.net/manual/en/ini.core.php#ini.max-file-uploads)
(default `20`) ini settings.
These settings control if any and how many files can be uploaded in a single request.
If you upload more files in a single request, additional files will be ignored
and the `getUploadedFiles()` method returns a truncated array.
Note that upload fields left blank on submission do not count towards this limit.
You can control the maximum number of file uploads per request by explicitly
passing the second parameter to the constructor like this:

```php
new React\Http\Middleware\RequestBodyParserMiddleware(10 * 1024, 100); // 100 files with 10 KiB each
```

> Note that this middleware handler simply parses everything that is already
  buffered in the request body.
  It is imperative that the request body is buffered by a prior middleware
  handler as given in the example above.
  This previous middleware handler is also responsible for rejecting incoming
  requests that exceed allowed message sizes (such as big file uploads).
  The [`RequestBodyBufferMiddleware`](#requestbodybuffermiddleware) used above
  simply discards excessive request bodies, resulting in an empty body.
  If you use this middleware without buffering first, it will try to parse an
  empty (streaming) body and may thus assume an empty data structure.
  See also [`RequestBodyBufferMiddleware`](#requestbodybuffermiddleware) for
  more details.
  
> PHP's `MAX_FILE_SIZE` hidden field is respected by this middleware.
  Files that exceed this limit will be rejected with an `UPLOAD_ERR_FORM_SIZE` error.

> This middleware respects the
  [`max_input_vars`](https://www.php.net/manual/en/info.configuration.php#ini.max-input-vars)
  (default `1000`) and
  [`max_input_nesting_level`](https://www.php.net/manual/en/info.configuration.php#ini.max-input-nesting-level)
  (default `64`) ini settings.

> Note that this middleware ignores the
  [`enable_post_data_reading`](https://www.php.net/manual/en/ini.core.php#ini.enable-post-data-reading)
  (default `1`) ini setting because it makes little sense to respect here and
  is left up to higher-level implementations.
  If you want to respect this setting, you have to check its value and
  effectively avoid using this middleware entirely.

## Install

The recommended way to install this library is [through Composer](https://getcomposer.org/).
[New to Composer?](https://getcomposer.org/doc/00-intro.md)

This project follows [SemVer](https://semver.org/).
This will install the latest supported version:

```bash
composer require react/http:^1.10
```

See also the [CHANGELOG](CHANGELOG.md) for details about version upgrades.

This project aims to run on any platform and thus does not require any PHP
extensions and supports running on legacy PHP 5.3 through current PHP 8+ and
HHVM.
It's *highly recommended to use the latest supported PHP version* for this project.

## Tests

To run the test suite, you first need to clone this repo and then install all
dependencies [through Composer](https://getcomposer.org/):

```bash
composer install
```

To run the test suite, go to the project root and run:

```bash
vendor/bin/phpunit
```

The test suite also contains a number of functional integration tests that rely
on a stable internet connection.
If you do not want to run these, they can simply be skipped like this:

```bash
vendor/bin/phpunit --exclude-group internet
```

## License

MIT, see [LICENSE file](LICENSE).
