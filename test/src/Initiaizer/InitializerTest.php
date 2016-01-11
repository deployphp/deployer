<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Initializer;

/**
 * Initializer testing
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
class InitializerTest extends \PHPUnit_Framework_TestCase
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
    public function setUp()
    {
        $this->initializer = new Initializer();
        $this->template = $this->getMockForAbstractClass('Deployer\Initializer\Template\TemplateInterface');
        $this->initializer->addTemplate('test', $this->template);
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown()
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
     *
     * @expectedException \Deployer\Initializer\Exception\TemplateNotFoundException
     * @expectedExceptionMessage Not found template with name "foo". Available templates: "test"
     */
    public function testWithTemplateNotFound()
    {
        $this->initializer->initialize('foo', sys_get_temp_dir(), 'deploy.php');
    }

    /**
     * Test with deployer configuration file already exist
     */
    public function testWithDeployerConfigurationFileAlreadyExist()
    {
        list($tmpDir, $tmpFileName, $tmpFilePath) = $this->createTemporaryFile();

        $this->setExpectedException(
            'Deployer\Initializer\Exception\IOException',
            sprintf(
                'Can not initialize deployer. The file "%s" already exist.',
                $tmpFilePath
            )
        );

        touch($tmpFilePath);

        $this->initializer->initialize('test', $tmpDir, $tmpFileName);
    }

    /**
     * Test with directory is not writable
     */
    public function testWithDirectoryIsNotWritable()
    {
        list($tmpDir) = $this->createTemporaryFile();

        $this->setExpectedException(
            'Deployer\Initializer\Exception\IOException',
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

        $this->setExpectedException(
            'Deployer\Initializer\Exception\IOException',
            sprintf(
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

        $this->setExpectedException(
            'Deployer\Initializer\Exception\IOException',
            sprintf(
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
