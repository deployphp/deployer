<?php

namespace Deployer\Configuration;

use Deployer\Exception\ConfigurationException;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    public function testParse()
    {
        $config = new Configuration();
        $config->set('foo', 'a');
        $config['bar'] = 'b';

        self::assertEquals('a b', $config->parse('{{foo}} {{bar}}'));
    }

    public function testUnset()
    {
        $config = new Configuration();
        $config->set('opt', true);
        unset($config['opt']);
        self::assertFalse(isset($config['opt']));
    }

    public function testGet()
    {
        $config = new Configuration();
        $config->set('opt', true);
        $config->set('fn', function () {
            return 'func';
        });

        self::assertTrue(isset($config['opt']));
        self::assertEquals(true, $config['opt']);
        self::assertEquals('func', $config['fn']);
    }

    public function testGetDefault()
    {
        $config = new Configuration();
        $config->set('name', 'alpha');

        self::assertEquals('/alpha', $config->get('path', '/{{name}}'));
    }

    public function testGetException()
    {
        $this->expectException(ConfigurationException::class);

        $config = new Configuration();
        $config->set('name', 'alpha');

        self::assertEquals('/alpha', $config->get('path'));
    }

    public function testGetParent()
    {
        $parent = new Configuration();
        $config = new Configuration($parent);

        $parent->set('opt', 'value');
        self::assertEquals('value', $parent['opt']);
        self::assertEquals('value', $config['opt']);

        $parent->set('opt', 'newValue');
        self::assertEquals('newValue', $parent['opt']);
        self::assertEquals('value', $config['opt']);

        $config->set('opt', 'hostValue');
        self::assertEquals('newValue', $parent['opt']);
        self::assertEquals('hostValue', $config['opt']);
        self::assertEquals('okay', $config->get('miss', 'okay'));
    }

    public function testGetParentParent()
    {
        $global = new Configuration();
        $parent = new Configuration($global);
        $config = new Configuration($parent);

        $global->set('global', 'value from {{path}}');
        $parent->set('path', 'parent');

        self::assertEquals('value from parent', $config->get('global'));
    }

    public function testGetParentWhatDependsOnChild()
    {
        $parent = new Configuration();
        $alpha = new Configuration($parent);
        $beta = new Configuration($parent);

        $parent->set('deploy_path', 'path/{{name}}');
        $alpha->set('name', 'alpha');
        $beta->set('name', 'beta');

        self::assertEquals('path/alpha', $alpha->get('deploy_path'));
        self::assertEquals('path/beta', $beta->get('deploy_path'));
    }

    public function testGetFromCallback()
    {
        $config = new Configuration();
        $config->set('func', function () {
            return 'param';
        });
        self::assertEquals('param', $config['func']);
    }

    public function testAdd()
    {
        $config = new Configuration();
        $config->set('opt', ['foo', 'bar']);
        $config->add('opt', ['baz']);
        self::assertEquals(['foo', 'bar', 'baz'], $config['opt']);
    }

    public function testAddEmpty()
    {
        $config = new Configuration();
        $config->add('opt', ['baz']);
        self::assertEquals(['baz'], $config['opt']);
    }

    public function testAddDefaultToNotArray()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Config option "config" isn\'t array.');

        $config = new Configuration();
        $config->set('config', 'option');
        $config->add('config', ['three']);
    }

    public function testAddToParent()
    {
        $parent = new Configuration();
        $alpha = new Configuration($parent);

        $parent->set('files', ['a', 'b']);
        $alpha->add('files', ['c']);

        self::assertEquals(['a', 'b', 'c'], $alpha->get('files'));
    }

    public function testAddToParentCallback()
    {
        $parent = new Configuration();
        $alpha = new Configuration($parent);

        $parent->set('files', function () {
            return ['a', 'b'];
        });
        $alpha->add('files', ['c']);

        self::assertEquals(['a', 'b', 'c'], $alpha->get('files'));
    }

    public function testPersist()
    {
        $parent = new Configuration();
        $alpha = new Configuration($parent);

        $parent->set('global', 'do not include');
        $alpha->set('whoami', function () {
            $this->fail('should not be called');
        });
        $alpha->set('name', 'alpha');

        self::assertEquals(['name' => 'alpha'], $alpha->persist());
    }
}
