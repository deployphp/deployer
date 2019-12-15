<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Initializer;

use Deployer\Initializer\Exception\IOException;
use Deployer\Initializer\Template\TemplateInterface;
use PHPUnit\Framework\TestCase;

/**
 * Initializer testing
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
class InitializerTest extends TestCase
{
    /**
     * @var Initializer
     */
    private $initializer;

    /**
     * @var \Deployer\Initializer\Template\TemplateInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $template;

    /**
     * @var string
     */
    private $tmpFilePath;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        $this->initializer = new Initializer();
        $this->template = $this->getMockForAbstractClass(TemplateInterface::class);
        $this->initializer->addTemplate('test', $this->template);
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown(): void
    {
        if ($this->tmpFilePath) {
            $dir = dirname($this->tmpFilePath);

            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            /** @var \SplFileInfo $file */
            foreach ($files as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }
        }
    }

    /**
     * Test with template not found
     */
    public function testWithTemplateNotFound()
    {
        $this->expectException(\Deployer\Initializer\Exception\TemplateNotFoundException::class);
        $this->expectExceptionMessage('Not found template with name "foo". Available templates: "test"');

        $this->initializer->initialize('foo', sys_get_temp_dir(), 'deploy.php');
    }

    /**
     * Test with deployer configuration file already exist
     */
    public function testWithDeployerConfigurationFileAlreadyExist()
    {
        list($tmpDir, $tmpFileName, $tmpFilePath) = $this->createTemporaryFile();

        $this->expectException(IOException::class);
        $this->expectExceptionMessage(sprintf('The file "%s" already exist.', $tmpFilePath));

        touch($tmpFilePath);

        $this->initializer->initialize('test', $tmpDir, $tmpFileName);
    }

    /**
     * Test with directory is not writable
     */
    public function testWithDirectoryIsNotWritable()
    {
        list($tmpDir) = $this->createTemporaryFile();

        $this->expectException(IOException::class);
        $this->expectExceptionMessage(
            sprintf(
                'The directory "%s" is not writable.',
                $tmpDir
            )
        );

        chmod($tmpDir, 0400);

        $this->initializer->initialize('test', $tmpDir);
    }

    /**
     * Test with parent directory is not writable
     */
    public function testWithParentDirectoryIsNotWritable()
    {
        list($tmpDir) = $this->createTemporaryFile();
        $tmpDir .= '/foo';

        $this->expectException(IOException::class);
        $this->expectExceptionMessage(sprintf(
                'Could not create directory "%s". Permission denied',
                $tmpDir . '/bar'
            )
        );

        mkdir($tmpDir);
        chmod($tmpDir, 0400);

        $tmpDir .= '/bar';

        $this->initializer->initialize('test', $tmpDir);
    }

    /**
     * Test with path already exist (file, not directory)
     */
    public function testWithPathAlreadyExist()
    {
        list($tmpDir) = $this->createTemporaryFile();
        $tmpDir .= '/foo';

        $this->expectException(IOException::class);
        $this->expectExceptionMessage(sprintf(
                'Can not create directory. The path "%s" already exist.',
                $tmpDir
            )
        );

        touch($tmpDir);

        $this->initializer->initialize('test', $tmpDir);
    }

    /**
     * Test successfully initialize deployer
     */
    public function testSuccessfullyInitialize()
    {
        list($tmpDir, $tmpFileName, $tmpFilePath) = $this->createTemporaryFile();

        $this->template->expects($this->once())->method('initialize')
            ->with($tmpFilePath);

        $configFilePath = $this->initializer->initialize('test', $tmpDir, $tmpFileName);

        $this->assertEquals($tmpFilePath, $configFilePath);
    }

    /**
     * Create a temporary file
     *
     * @return array
     */
    private function createTemporaryFile()
    {
        $tmpFileName = md5(uniqid(mt_rand(), true)) . '.php';
        $tmpDir = sys_get_temp_dir() . '/' . uniqid();
        mkdir($tmpDir, 0775);
        $tmpFilePath = $tmpDir . '/' . $tmpFileName;

        return [$tmpDir, $tmpFileName, $tmpFilePath];
    }
}
