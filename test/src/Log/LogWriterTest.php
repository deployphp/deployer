<?php

namespace Deployer\Log;

class LogWriterTest extends \PHPUnit_Framework_TestCase
{
    public function testLogWriter()
    {
        $app = new LogWriter('path');
        $this->assertTrue(method_exists($app, 'writeLog'), 'Class does not have method writeLog');
    }
}
