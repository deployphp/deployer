<?php

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Host;

use Deployer\Configuration\Configuration;
use Deployer\Exception\ConfigurationException;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    public function testConfiguration()
    {
        $config = new Configuration();
        $config->set('int', 42);
        $config->set('string', 'value');
        $config->set('array', [1, 'two']);
        $config->set('hyphen-ated', 'hyphen');
        $config->set('parse', 'is {{int}}');
        $config->set('parse-hyphen', 'has {{hyphen-ated}}');
        $config->set('callback', function () {
            return 'callback';
        });
        $this->assertEquals(42, $config->get('int'));
        $this->assertEquals('value', $config->get('string'));
        $this->assertEquals([1, 'two'], $config->get('array'));
        $this->assertEquals('default', $config->get('no', 'default'));
        $this->assertEquals(null, $config->get('no', null));
        $this->assertEquals('callback', $config->get('callback'));
        $this->assertEquals('is 42', $config->get('parse'));
        $this->assertEquals('has hyphen', $config->get('parse-hyphen'));

        $config->set('int', 11);
        $this->assertEquals('is 11', $config->get('parse'));

        $this->expectException('RuntimeException');
        $config->get('so');
    }

    public function testAddParams()
    {
        $config = new Configuration();
        $config->set('config', [
            'one',
            'two' => 2,
            'nested' => [],
        ]);
        $config->add('config', [
            'two' => 20,
            'nested' => [
                'first',
            ],
        ]);
        $config->add('config', [
            'nested' => [
                'second',
            ],
        ]);
        $config->add('config', [
            'extra',
        ]);

        $expected = [
            'one',
            'two' => 20,
            'nested' => [
                'first',
                'second',
            ],
            'extra',
        ];
        $this->assertEquals($expected, $config->get('config'));
    }

    public function testAddParamsToNotArray()
    {
        $this->expectException(ConfigurationException::class);

        $config = new Configuration();
        $config->set('config', 'option');
        $config->add('config', ['three']);
    }
}
