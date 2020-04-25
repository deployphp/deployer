<?php

namespace Deployer\Configuration;

use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    public function testAdd()
    {
        $config = new Configuration();
        $config->set('opt', ['foo', 'bar']);
        $config->add('opt', ['baz']);
        self::assertEquals(['foo', 'bar', 'baz'], $config['opt']);
    }

    public function testAddDefaultToNotArray()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Configuration parameter `config` isn\'t array.');

        $config = new Configuration();
        $config->set('config', 'option');
        $config->add('config', ['three']);
    }

    public function testParse()
    {
        $config = new Configuration();
        $config->set('foo', 'a');
        $config->set('bar', 'b');
        self::assertEquals('a b', $config->parse('{{foo}} {{bar}}'));
    }

    public function testGet()
    {
        $config = new Configuration();
        $config->set('opt', true);
        self::assertEquals(true, $config['opt']);
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
    }
}
