<?php

namespace Ssh;

use RuntimeException;

/**
 * Abstract class for the SSH subsystems as Sftp and Publickey
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
abstract class Subsystem extends AbstractResourceHolder
{
    protected $session;
    protected $resource;

    /**
     * Constructor
     *
     * @param  mixed $session A Session instance or a SSH session resource
     */
    public function __construct($session)
    {
        if (!$session instanceof Session && !is_resource($session)) {
            throw new \InvalidArgumentException('The session must be either a Session instance or a SSH session resource.');
        }

        $this->session = $session;
    }

    /**
     * Returns the SSH session resource
     *
     * @return resource
     */
    public function getSessionResource()
    {
        if ($this->session instanceof Session) {
            return $this->session->getResource();
        } else {
            return $this->session;
        }
    }
}
