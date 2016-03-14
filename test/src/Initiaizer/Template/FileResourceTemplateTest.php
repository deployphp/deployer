<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Initializer\Template;

/**
 * Test file resource template
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
class FileResourceTemplateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test with file resource not found
     */
    public function testWithFileResourceNotFound()
    {
        $fileResource = sys_get_temp_dir() . '/' . uniqid();

        $this->setExpectedException(
            'Deployer\Initializer\Exception\ResourceNotFoundException',
            sprintf(
                'Not found resource file "%s".',
                $fileResource
            )
        );

        $template = $this->createMockForFileResourceTemplate();
        $template->expects($this->once())->method('getFilePathOfResource')
            ->will($this->returnValue($fileResource));

        $template->initialize(sys_get_temp_dir() . '/' . uniqid());
    }

    /**
     * Test successfully initialize for template
     */
    public function testSuccessfully()
    {
        $resource = <<<RESOURCE
<?php
require 'recipe/common.php';
set('repository', '%repository%');

RESOURCE;

        $tmpDir = sys_get_temp_dir() . '/' . uniqid();
        mkdir($tmpDir);

        $fileResource = $tmpDir . '/foo.php.dist';
        touch($fileResource);
        file_put_contents($fileResource, $resource);

        $template = $this->createMockForFileResourceTemplate();
        $template->expects($this->once())->method('getFilePathOfResource')
            ->will($this->returnValue($fileResource));

        $template->expects($this->once())->method('getParametersForReplace')
            ->will($this->returnValue([
                'repository' => 'git://domain.com:foo/bar.git'
            ]));

        $template->initialize($tmpDir . '/foo.php');

        $this->assertTrue(
            file_exists($tmpDir . '/foo.php') && is_file($tmpDir . '/foo.php'),
            'The file not created'
        );

        $generatedResource = file_get_contents($tmpDir . '/foo.php');
        $expectedResource = <<<RESOURCE
<?php
require 'recipe/common.php';
set('repository', 'git://domain.com:foo/bar.git');

RESOURCE;
        $this->assertEquals($expectedResource, $generatedResource, 'Invalid resource');
    }

    /**
     * Create mock for file resource template
     *
     * @return \Deployer\Initializer\Template\FileResourceTemplate|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createMockForFileResourceTemplate()
    {
        return $this->getMockForAbstractClass(
            'Deployer\Initializer\Template\FileResourceTemplate',
            [],
            '',
            true,
            true,
            true,
            ['getParametersForReplace']
        );
    }
}
