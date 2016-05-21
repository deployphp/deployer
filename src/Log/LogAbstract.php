<?php

namespace Deployer\Log;

use Monolog\Logger;

class LogAbstract
{
    /**
     * @var
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
                $this->log->addRecord($level, strip_tags($line));
            }
        } else {
            $this->log->addRecord($level, strip_tags($message));
        }
    }
}