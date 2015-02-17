<?php

namespace Ssh;

/**
 * Interface that must be implemented by that handle a resource
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
interface ResourceHolder
{
    /**
     * Returns the underlying resource
     *
     * @return resource
     */
    function getResource();
}
