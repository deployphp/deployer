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

        $formatter = new LineFormatter(null, null, false, true);

        $handler = new StreamHandler($path);
        $handler->setFormatter($formatter);
        $this->log->pushHandler($handler);

        return $this;
    }
}
