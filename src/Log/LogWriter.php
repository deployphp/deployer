<?php

namespace Deployer\Log;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LogWriter extends LogAbstract
{
    /**
     * LogWriter constructor.
     * @param $path
     */
    public function __construct($path)
    {
        $this->log = new Logger('Deployer');
        $this->log->pushHandler(new StreamHandler($path));
        return $this;
    }
}
