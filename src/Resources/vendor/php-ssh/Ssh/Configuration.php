<?php

namespace Ssh;

/**
 * Configuration of an SSH connection
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
class Configuration
{
    protected $host;
    protected $port;
    protected $methods;
    protected $callbacks;
    protected $identity;

    /**
     * Constructor
     *
     * @param  string  $host
     * @param  integer $port
     * @param  array   $methods
     * @param  array   $callbacks
     * @param  string  $identity
     */
    public function __construct($host, $port = 22, array $methods = array(), array $callbacks = array(), $identity = null)
    {
        $this->host      = $host;
        $this->port      = $port;
        $this->methods   = $methods;
        $this->callbacks = $callbacks;
        $this->identity  = $identity;
    }

    /**
     * Defines the host
     *
     * @param  string $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * Returns the host
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Defines the port
     *
     * @param  integer $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * Returns the port
     *
     * @return integer
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Defines the methods
     *
     * @param  array $methods
     */
    public function setMethods(array $methods)
    {
        $this->methods = $methods;
    }

    /**
     * Returns the methods
     *
     * @return array
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * Defines the callbacks
     *
     * @param array $callbacks
     */
    public function setCallbacks(array $callbacks)
    {
        $this->callbacks = $callbacks;
    }

    /**
     * Returns the callbacks
     *
     * @return array $callbacks
     */
    public function getCallbacks()
    {
        return $this->callbacks;
    }

    /**
     * Returns an array of argument designed for the ssh2_connect function
     *
     * @return array
     */
    public function asArguments()
    {
        return array(
            $this->host,
            $this->port,
            $this->methods,
            $this->callbacks
        );
    }

    /**
     * @return string
     */
    public function getIdentity()
    {
        return $this->identity;
    }

    /**
     * @param string $identity
     */
    public function setIdentity($identity)
    {
        $this->identity = $identity;
    }
}
