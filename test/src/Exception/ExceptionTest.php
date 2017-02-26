<?php

namespace Deployer\Exception;

use PHPUnit\Framework\TestCase;

class ExceptionTest extends TestCase
{
    public function testConstructor()
    {
        $message = 'Error Message';
        $code = 1;
        $previous = new \Exception('Previous Message');

        $exception = new Exception($message, $code, $previous);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertEquals($previous, $exception->getPrevious());
    }
}