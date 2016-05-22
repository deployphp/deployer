<?php

namespace Deployer\Log;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LogWriter extends LogAbstract
{
    /**
     * LogWriter constructor.
     * @param $name
     * @param $path
     */
    public function __construct($name, $path)
    {
        $this->log = new Logger($name);
        $this->log->pushHandler(new StreamHandler($path));

        return $this;
    }
}
