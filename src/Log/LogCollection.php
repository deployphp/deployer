<?php

namespace Deployer\Log;

use Deployer\Collection\Collection;

class LogCollection extends Collection
{
    /**
     * @param string $name
     * @return Log
     */
    public function get($name)
    {
        return parent::get($name);
    }
}
