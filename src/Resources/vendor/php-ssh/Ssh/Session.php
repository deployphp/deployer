<?php

namespace Ssh;

use InvalidArgumentException, RuntimeException;

/**
 * SSH session
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
class Session extends AbstractResourceHolder
{
    protected $configuration;
    protected $authentication;
    protected $subsystems;

    /**
     * Constructor
     *
     * @param  Configuration  A Configuration instance
     * @param  Authentication An optional Authentication instance
     */
    public function __construct(Configuration $configuration, Authentication $authentication = null)
    {
        $this->configuration  = $configuration;
        $this->authentication = $authentication;
        $this->subsystem      = array();
    }

    /**
     * Defines the authentication. If the
     *
     * @param  Authentication $authentication
     */
    public function setAuthentication(Authentication $authentication)
    {
        $firstAuthentication = null === $this->authentication;

        $this->authentication = $authentication;

        if ($firstAuthentication && is_resource($this->resource)) {
            $this->authenticate();
        }
    }

    /**
     * Returns the Sftp subsystem
     *
     * @return Sftp
     */
    public function getSftp()
    {
        return $this->getSubsystem('sftp');
    }

    /**
     * Returns the Publickey subsystem
     *
     * @return Publickey
     */
    public function getPublickey()
    {
        return $this->getSubsystem('publickey');
    }

    /**
     * Returns the Exec subsystem
     *
     * @return Exec
     */
    public function getExec()
    {
        return $this->getSubsystem('exec');
    }

    /**
     * Returns the specified subsystem
     *
     * If the subsystem does not exists, it will create it
     *
     * @param  string $name The subsystem's name
     *
     * @return Subsystem
     */
    public function getSubsystem($name)
    {
        if (!isset($this->subsystems[$name])) {
            $this->createSubsystem($name);
        }

        return $this->subsystems[$name];
    }

    /**
     * Creates the specified subsystem
     *
     * @param  string $name The subsystem's name
     *
     * @throws InvalidArgumentException if the specified subsystem is no
     *                                  supported (e.g does not exist)
     */
    protected function createSubsystem($name)
    {
        switch ($name) {
            case 'sftp':
                $subsystem = new Sftp($this);
                break;
            case 'publickey':
                $subsystem = new Publickey($this);
                break;
            case 'exec':
                $subsystem = new Exec($this);
                break;
            default:
                throw new InvalidArgumentException(sprintf('The subsystem \'%s\' is not supported.', $name));
        }

        $this->subsystems[$name] = $subsystem;
    }

    /**
     * Creates the session resource
     *
     * If there is a defined authentication, it will authenticate the session
     *
     * @throws RuntimeException if the connection fail
     */
    protected function createResource()
    {
        $resource = $this->connect($this->configuration->asArguments());

        if (!is_resource($resource)) {
            throw new RuntimeException('The SSH connection failed.');
        }

        $this->resource = $resource;

        if (null !== $this->authentication) {
            $this->authenticate();
        }
    }

    /**
     * Opens a connection with the remote server using the given arguments
     *
     * @param  array $arguments An array of arguments
     *
     * @return resource
     */
    protected function connect(array $arguments)
    {
        return call_user_func_array('ssh2_connect', $arguments);
    }

    /**
     * Authenticates over the current SSH session and using the defined
     * authentication
     *
     * @throws RuntimeException on authentication failure
     */
    protected function authenticate()
    {
        $authenticated = $this->authentication->authenticate($this->resource);

        if (!$authenticated) {
            throw new RuntimeException('The authentication over the current SSH connection failed.');
        }
    }
    
    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }
}
