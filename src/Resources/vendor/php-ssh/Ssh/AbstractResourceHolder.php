<?php

namespace Ssh;

use RuntimeException;

/**
 * An abstract resource holder
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
abstract class AbstractResourceHolder implements ResourceHolder
{
    protected $resource;

    /**
     * Returns the underlying resource. If the resource does not exist, it will
     * create it
     *
     * @return resource
     */
    public function getResource()
    {
        if (!is_resource($this->resource)) {
            $this->createResource();
        }

        return $this->resource;
    }

    /**
     * Creates the underlying resource
     *
     * @throws RuntimeException on resource creation failure
     */
    abstract protected function createResource();
}
