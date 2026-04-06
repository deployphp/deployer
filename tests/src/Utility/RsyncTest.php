<?php

declare(strict_types=1);

namespace Deployer\Utility;

use Deployer\Deployer;
use Deployer\Host\Localhost;
use Deployer\Logger\Logger;
use Deployer\Logger\Handler\HandlerInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;

#[Group('integration')]
class RsyncTest extends TestCase
{
    private Deployer $deployer;
    private Localhost $host;

    protected function setUp(): void
    {
        $console = new Application();
        $input = $this->createStub(Input::class);
        $output = $this->createStub(Output::class);

        $this->deployer = new Deployer($console);
        $this->deployer['input'] = $input;
        $this->deployer['output'] = $output;

        $this->host = new Localhost('test-host');
    }

    protected function tearDown(): void
    {
        unset($this->deployer);
    }

    public function testCallWithLocalPaths(): void
    {
        $srcDir = sys_get_temp_dir() . '/deployer_rsync_src_' . uniqid();
        $dstDir = sys_get_temp_dir() . '/deployer_rsync_dst_' . uniqid();
        mkdir($srcDir, 0777, true);
        mkdir($dstDir, 0777, true);
        file_put_contents("$srcDir/test.txt", 'hello');

        try {
            $output = new BufferedOutput();
            $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
            $logger = new Logger($output, $this->createStub(HandlerInterface::class));
            $rsync = new Rsync($output, $logger);

            $rsync->call($this->host, "$srcDir/", $dstDir, [
                'progress_bar' => false,
            ]);

            self::assertFileExists("$dstDir/test.txt");
            self::assertSame('hello', file_get_contents("$dstDir/test.txt"));
        } finally {
            @unlink("$srcDir/test.txt");
            @unlink("$dstDir/test.txt");
            @rmdir($srcDir);
            @rmdir($dstDir);
        }
    }

    public function testCallOutputsCommandWhenVerbose(): void
    {
        $srcDir = sys_get_temp_dir() . '/deployer_rsync_src_' . uniqid();
        $dstDir = sys_get_temp_dir() . '/deployer_rsync_dst_' . uniqid();
        mkdir($srcDir, 0777, true);
        mkdir($dstDir, 0777, true);

        try {
            $output = new BufferedOutput();
            $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
            $logger = new Logger($output, $this->createStub(HandlerInterface::class));
            $rsync = new Rsync($output, $logger);

            $rsync->call($this->host, "$srcDir/", $dstDir, [
                'progress_bar' => false,
            ]);

            $result = $output->fetch();
            self::assertStringContainsString('rsync', $result);
            self::assertStringContainsString('-azP', $result);
        } finally {
            @rmdir($srcDir);
            @rmdir($dstDir);
        }
    }

    public function testCallWithCustomFlags(): void
    {
        $srcDir = sys_get_temp_dir() . '/deployer_rsync_src_' . uniqid();
        $dstDir = sys_get_temp_dir() . '/deployer_rsync_dst_' . uniqid();
        mkdir($srcDir, 0777, true);
        mkdir($dstDir, 0777, true);

        try {
            $output = new BufferedOutput();
            $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
            $logger = new Logger($output, $this->createStub(HandlerInterface::class));
            $rsync = new Rsync($output, $logger);

            $rsync->call($this->host, "$srcDir/", $dstDir, [
                'flags' => '-az',
                'progress_bar' => false,
            ]);

            $result = $output->fetch();
            self::assertStringContainsString('-az', $result);
        } finally {
            @rmdir($srcDir);
            @rmdir($dstDir);
        }
    }

    public function testCallWithMultipleSources(): void
    {
        $srcDir1 = sys_get_temp_dir() . '/deployer_rsync_src1_' . uniqid();
        $srcDir2 = sys_get_temp_dir() . '/deployer_rsync_src2_' . uniqid();
        $dstDir = sys_get_temp_dir() . '/deployer_rsync_dst_' . uniqid();
        mkdir($srcDir1, 0777, true);
        mkdir($srcDir2, 0777, true);
        mkdir($dstDir, 0777, true);
        file_put_contents("$srcDir1/a.txt", 'a');
        file_put_contents("$srcDir2/b.txt", 'b');

        try {
            $output = new BufferedOutput();
            $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
            $logger = new Logger($output, $this->createStub(HandlerInterface::class));
            $rsync = new Rsync($output, $logger);

            $rsync->call($this->host, ["$srcDir1/", "$srcDir2/"], $dstDir, [
                'progress_bar' => false,
            ]);

            self::assertFileExists("$dstDir/a.txt");
            self::assertFileExists("$dstDir/b.txt");
        } finally {
            @unlink("$srcDir1/a.txt");
            @unlink("$srcDir2/b.txt");
            @unlink("$dstDir/a.txt");
            @unlink("$dstDir/b.txt");
            @rmdir($srcDir1);
            @rmdir($srcDir2);
            @rmdir($dstDir);
        }
    }
}
