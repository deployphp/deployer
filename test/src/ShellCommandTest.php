<?php
namespace Deployer;

class ShellCommandTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->command = new ShellCommand("echo '<secret>important\nsecret</secret>' > /dev/null");
        $this->emptySecretCommand = new ShellCommand("echo '<secret></secret>' > /dev/null");
    }

    public function testGetForRunning()
    {
        $this->assertEquals(
            "echo 'important\nsecret' > /dev/null",
            $this->command->getForRunning()
        );
    }

    public function testToString()
    {
        $this->assertEquals(
            "echo 'important\nsecret' > /dev/null",
            (string) $this->command
        );
    }

    public function testGetForPrinting()
    {
        $this->assertEquals(
            "echo '[SECRET HIDDEN]' > /dev/null",
            $this->command->getForPrinting()
        );
    }

    public function testGetForRunningEmptySecret()
    {
        $this->assertEquals(
            "echo '' > /dev/null",
            $this->emptySecretCommand->getForRunning()
        );
    }

    public function testGetForPrintingEmptySecret()
    {
        $this->assertEquals(
            "echo '[SECRET HIDDEN]' > /dev/null",
            $this->emptySecretCommand->getForPrinting()
        );
    }
}
