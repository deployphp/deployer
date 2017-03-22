<?php

namespace Deployer\Exception;

use PHPUnit\Framework\TestCase;

class ForwardExceptionTest extends TestCase
{
    public function testConstructor()
    {
        $serverName = 'localhost';
        $exceptionClass = \Exception::class;
        $message = 'Error Message';

        $exception = new ForwardException($serverName, $exceptionClass, $message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}
