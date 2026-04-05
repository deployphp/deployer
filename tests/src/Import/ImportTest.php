<?php

declare(strict_types=1);

namespace Deployer\Import;

use Deployer\Deployer;
use Deployer\Exception\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;

class ImportTest extends TestCase
{
    private Deployer $deployer;

    public function setUp(): void
    {
        $console = new Application();
        $input = $this->createStub(Input::class);
        $output = $this->createStub(Output::class);

        $this->deployer = new Deployer($console);
        $this->deployer['input'] = $input;
        $this->deployer['output'] = $output;
    }

    public function tearDown(): void
    {
        unset($this->deployer);
    }

    public function testUnknownFileFormatThrows(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unknown file format');

        Import::import('file.txt');
    }
}
