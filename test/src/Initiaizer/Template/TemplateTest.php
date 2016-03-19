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
 * @author Anton Medvedev <anton@medv.io>
 *
 */
class TemplateTest extends \PHPUnit_Framework_TestCase
{
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

        $template = $this->createMockForFileResourceTemplate();
        $template->expects($this->once())->method('getTemplateContent')
            ->will($this->returnValue($resource));

        $template->expects($this->once())->method('getParametersForReplace')
            ->will($this->returnValue([
                'repository' => 'git://domain.com:foo/bar.git'
            ]));

        $tmpDir = sys_get_temp_dir() . '/' . uniqid();
        mkdir($tmpDir);

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
     * @return \Deployer\Initializer\Template\Template|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createMockForFileResourceTemplate()
    {
        return $this->getMockForAbstractClass(
            'Deployer\Initializer\Template\Template',
            [],
            '',
            true,
            true,
            true,
            ['getParametersForReplace']
        );
    }
}
