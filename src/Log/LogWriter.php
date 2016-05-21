<?php

namespace Deployer\Log;

use Monolog\Handler\BufferHandler;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\NativeMailerHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LogWriter
{

    private $log;

    public function __construct($name)
    {
        $this->log = new Logger($name);
    }

    public function localPath($pathData)
    {
        $path = $this->log->getName() . "_log.txt";
        $level = Logger::DEBUG;

        extract($pathData);
        
        $this->log->pushHandler(new StreamHandler($path, $level));

        return $this;
    }

    public function mail($emailData)
    {
        //Default values
        $from = $this->log->getName() . "_deploy@localhost";
        $subject = $this->log->getName() . " deploy log";
        $level = Logger::ERROR;

        //Extract data to vars
        extract($emailData);
        $to = $emailData['to'];

        $emailHandler = new NativeMailerHandler($to, $subject, $from, Logger::DEBUG);
        $bufferHandler = new BufferHandler($emailHandler, 200);
        $findersCrossedHandler = new FingersCrossedHandler($bufferHandler, $level);
        $this->log->pushHandler($findersCrossedHandler);

        return $this;
    }

    public function writeLog($message, $level = Logger::DEBUG)
    {
        if (is_array($message)){
            foreach ($message as $line) {
                $this->log->addRecord($level, $line);
            }
        }
        else {
            $this->log->addRecord($level, $message);
        }
    }
}
