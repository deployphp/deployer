<?php

namespace React\Http\Client;

use Psr\Http\Message\RequestInterface;
use React\Http\Io\ClientConnectionManager;
use React\Http\Io\ClientRequestStream;

/**
 * @internal
 */
class Client
{
    /** @var ClientConnectionManager */
    private $connectionManager;

    public function __construct(ClientConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    /** @return ClientRequestStream */
    public function request(RequestInterface $request)
    {
        return new ClientRequestStream($this->connectionManager, $request);
    }
}
