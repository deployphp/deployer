<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
namespace Deployer\Server;

class EnvironmentTest extends \PHPUnit_Framework_TestCase
{
    public function testEnvironment()
    {
        Environment::setDefault('default', 'default');
        Environment::setDefault('callback', function () {
            return 'callback';
        });
        $env = new Environment();
        
        $env->set('int', 42);
        $env->set('string', 'value');
        $env->set('array', [1, 'two']);
        $env->set('parse', 'is {int}');
        
        $this->assertEquals(42, $env->get('int'));
        $this->assertEquals('value', $env->get('string'));
        $this->assertEquals([1, 'two'], $env->get('array'));
        $this->assertEquals('default', $env->get('no', 'default'));
        $this->assertEquals('default', $env->get('default'));
        $this->assertEquals('default', Environment::getDefault('default'));
        $this->assertEquals('callback', $env->get('callback'));
        $this->assertEquals('is 42', $env->get('parse'));
        $env->set('int', 11);
        $this->assertEquals('is 11', $env->get('parse'));

        $this->setExpectedException('RuntimeException', 'Environment parameter `so` does not exists.');
        $env->get('so');
    }
}
