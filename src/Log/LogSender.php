<?php

namespace Deployer\Log;

use Monolog\Formatter\HtmlFormatter;
use Monolog\Handler\BufferHandler;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\NativeMailerHandler;
use Monolog\Logger;

class LogSender extends LogAbstract
{

    /**
     * @var Logger
     */
    protected $log;

    /**
     * @var string
     */
    private $subject;

    /**
     * @var
     */
    private $to;

    /**
     * @var string
     */
    private $from;

    /**
     * @var int
     */
    private $level;

    /**
     * @var bool
     */
    private $loaded;
    

    /**
     * LogSender constructor.
     * @param $name
     * @param $to
     */
    public function __construct($name, $to)
    {
        $this->log = new Logger($name);
        $this->to = $to;
        $this->from = "deployer@".$name;
        $this->subject = $name." deploy log";
        $this->level = Logger::ERROR;
        $this->loaded = false;
        
        return $this;
    }

    /**
     * LogSender Initialize
     * @return $this
     */
    public function init()
    {

        $emailHandler = new NativeMailerHandler($this->to, $this->subject, $this->from, Logger::DEBUG);
        $emailHandler->setFormatter(new HtmlFormatter());
        $emailHandler->addHeader("Content-Type: text/html;");

        $bufferHandler = new BufferHandler($emailHandler, 200);
        $findersCrossedHandler = new FingersCrossedHandler($bufferHandler, $this->level);
        $this->log->pushHandler($findersCrossedHandler);

        $this->loaded = true;
        
        return $this;
    }

    /**
     * @param $message
     * @param int $level
     */
    public function writeLog($message, $level = Logger::DEBUG)
    {
        if (!$this->loaded) $this->init();
        
        parent::writeLog($message, $level); 
    }
}
