<?php

namespace React\Socket;

use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;

final class SocketServer extends EventEmitter implements ServerInterface
{
    private $server;

    /**
     * The `SocketServer` class is the main class in this package that implements the `ServerInterface` and
     * allows you to accept incoming streaming connections, such as plaintext TCP/IP or secure TLS connection streams.
     *
     * ```php
     * $socket = new React\Socket\SocketServer('127.0.0.1:0');
     * $socket = new React\Socket\SocketServer('127.0.0.1:8000');
     * $socket = new React\Socket\SocketServer('127.0.0.1:8000', $context);
     * ```
     *
     * This class takes an optional `LoopInterface|null $loop` parameter that can be used to
     * pass the event loop instance to use for this object. You can use a `null` value
     * here in order to use the [default loop](https://github.com/reactphp/event-loop#loop).
     * This value SHOULD NOT be given unless you're sure you want to explicitly use a
     * given event loop instance.
     *
     * @param string         $uri
     * @param array          $context
     * @param ?LoopInterface $loop
     * @throws \InvalidArgumentException if the listening address is invalid
     * @throws \RuntimeException if listening on this address fails (already in use etc.)
     */
    public function __construct($uri, array $context = array(), LoopInterface $loop = null)
    {
        // apply default options if not explicitly given
        $context += array(
            'tcp' => array(),
            'tls' => array(),
            'unix' => array()
        );

        $scheme = 'tcp';
        $pos = \strpos($uri, '://');
        if ($pos !== false) {
            $scheme = \substr($uri, 0, $pos);
        }

        if ($scheme === 'unix') {
            $server = new UnixServer($uri, $loop, $context['unix']);
        } elseif ($scheme === 'php') {
            $server = new FdServer($uri, $loop);
        } else {
            if (preg_match('#^(?:\w+://)?\d+$#', $uri)) {
                throw new \InvalidArgumentException(
                    'Invalid URI given (EINVAL)',
                    \defined('SOCKET_EINVAL') ? \SOCKET_EINVAL : 22
                );
            }

            $server = new TcpServer(str_replace('tls://', '', $uri), $loop, $context['tcp']);

            if ($scheme === 'tls') {
                $server = new SecureServer($server, $loop, $context['tls']);
            }
        }

        $this->server = $server;

        $that = $this;
        $server->on('connection', function (ConnectionInterface $conn) use ($that) {
            $that->emit('connection', array($conn));
        });
        $server->on('error', function (\Exception $error) use ($that) {
            $that->emit('error', array($error));
        });
    }

    public function getAddress()
    {
        return $this->server->getAddress();
    }

    public function pause()
    {
        $this->server->pause();
    }

    public function resume()
    {
        $this->server->resume();
    }

    public function close()
    {
        $this->server->close();
    }

    /**
     * [internal] Internal helper method to accept new connection from given server socket
     *
     * @param resource $socket server socket to accept connection from
     * @return resource new client socket if any
     * @throws \RuntimeException if accepting fails
     * @internal
     */
    public static function accept($socket)
    {
        $newSocket = @\stream_socket_accept($socket, 0);

        if (false === $newSocket) {
            // Match errstr from PHP's warning message.
            // stream_socket_accept(): accept failed: Connection timed out
            $error = \error_get_last();
            $errstr = \preg_replace('#.*: #', '', $error['message']);
            $errno = self::errno($errstr);

            throw new \RuntimeException(
                'Unable to accept new connection: ' . $errstr . self::errconst($errno),
                $errno
            );
        }

        return $newSocket;
    }

    /**
     * [Internal] Returns errno value for given errstr
     *
     * The errno and errstr values describes the type of error that has been
     * encountered. This method tries to look up the given errstr and find a
     * matching errno value which can be useful to provide more context to error
     * messages. It goes through the list of known errno constants when
     * ext-sockets is available to find an errno matching the given errstr.
     *
     * @param string $errstr
     * @return int errno value (e.g. value of `SOCKET_ECONNREFUSED`) or 0 if not found
     * @internal
     * @copyright Copyright (c) 2018 Christian Lück, taken from https://github.com/clue/errno with permission
     * @codeCoverageIgnore
     */
    public static function errno($errstr)
    {
        if (\function_exists('socket_strerror')) {
            foreach (\get_defined_constants(false) as $name => $value) {
                if (\strpos($name, 'SOCKET_E') === 0 && \socket_strerror($value) === $errstr) {
                    return $value;
                }
            }
        }

        return 0;
    }

    /**
     * [Internal] Returns errno constant name for given errno value
     *
     * The errno value describes the type of error that has been encountered.
     * This method tries to look up the given errno value and find a matching
     * errno constant name which can be useful to provide more context and more
     * descriptive error messages. It goes through the list of known errno
     * constants when ext-sockets is available to find the matching errno
     * constant name.
     *
     * Because this method is used to append more context to error messages, the
     * constant name will be prefixed with a space and put between parenthesis
     * when found.
     *
     * @param int $errno
     * @return string e.g. ` (ECONNREFUSED)` or empty string if no matching const for the given errno could be found
     * @internal
     * @copyright Copyright (c) 2018 Christian Lück, taken from https://github.com/clue/errno with permission
     * @codeCoverageIgnore
     */
    public static function errconst($errno)
    {
        if (\function_exists('socket_strerror')) {
            foreach (\get_defined_constants(false) as $name => $value) {
                if ($value === $errno && \strpos($name, 'SOCKET_E') === 0) {
                    return ' (' . \substr($name, 7) . ')';
                }
            }
        }

        return '';
    }
}
