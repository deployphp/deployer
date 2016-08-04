<?php

namespace Deployer\Log;

use Monolog\Logger;

class LogAbstract
{
    /**
     * @var Logger
     */
    protected $log;

    /**
     * @param $message
     * @param int $level
     */
    public function writeLog($message, $level = Logger::DEBUG)
    {
        if (is_array($message)) {
            foreach ($message as $line) {
                $this->log->addRecord($level, strip_tags($line) . "\n");
            }
        } else {
            $this->log->addRecord($level, strip_tags($message) . "\n");
        }
    }
}
