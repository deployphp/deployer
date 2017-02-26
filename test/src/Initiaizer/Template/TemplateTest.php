<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Initializer\Template;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Test file resource template
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 * @author Anton Medvedev <anton@medv.io>
 *
 */
class TemplateTest extends TestCase
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

    public function testParametersForReplace()
    {
        $class = new ReflectionClass(Template::class);
        $targetMethod = $class->getMethod('getParametersForReplace');
        $targetMethod->setAccessible(true);

        $replacements = $targetMethod->invoke($this->getMockForAbstractClass(Template::class));
        $this->assertEquals([], $replacements);
    }

    /**
     * Create mock for file resource template
     *
     * @return \Deployer\Initializer\Template\Template|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createMockForFileResourceTemplate()
    {
        return $this->getMockForAbstractClass(
            Template::class,
            [],
            '',
            true,
            true,
            true,
            ['getParametersForReplace']
        );
    }

    public function recipes()
    {
        return [
            [CakeTemplate::class, 'cakephp'],
            [CodeIgniterTemplate::class, 'codeigniter'],
            [DrupalTemplate::class, 'drupal8'],
            [LaravelTemplate::class, 'laravel'],
            [SymfonyTemplate::class, 'symfony'],
            [YiiTemplate::class, 'yii'],
            [ZendTemplate::class, 'zend_framework'],
        ];
    }

    /**
     * @dataProvider recipes
     * @param string $class
     * @param string $recipeName
     */
    public function testGetRecipe($class, $recipeName)
    {
        $templateClass = new ReflectionClass($class);
        $targetMethod = $templateClass->getMethod('getRecipe');
        $targetMethod->setAccessible(true);

        $getRecipeResult = $targetMethod->invoke(new $class);
        $this->assertEquals($recipeName, $getRecipeResult);
    }

    public function testCommonRecipe()
    {
        $templateClass = new ReflectionClass(CommonTemplate::class);
        $targetMethod = $templateClass->getMethod('getTemplateContent');
        $targetMethod->setAccessible(true);

        $getRecipeContent = $targetMethod->invoke(new CommonTemplate());
        $this->assertStringStartsWith('<?php', $getRecipeContent);
    }
}
