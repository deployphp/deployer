<?php

namespace Deployer\Ssh;

use Deployer\Host\Host;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
class ArgumentsTest extends TestCase
{
    public function testImmutable()
    {
        $arguments1 = new Arguments;
        $arguments2 = $arguments1->withOption('Test', 'immutable');
        $arguments3 = $arguments2->withFlag('-T');
        $arguments4 = $arguments3->withOptions(['Replace', 'all']);
        $arguments5 = $arguments4->withFlags(['-R']);
        $arguments6 = $arguments5->withDefaults((new Arguments)->withFlags(['-S']));

        static::assertNotSame($arguments1, $arguments2);
        static::assertNotSame($arguments2, $arguments3);
        static::assertNotSame($arguments3, $arguments4);
        static::assertNotSame($arguments4, $arguments5);
        static::assertNotSame($arguments5, $arguments6);
    }

    public function testDefaultsDoNotOverride()
    {
        $arguments = (new Arguments)->withFlags(['-A'])->withOptions(['Option' => 'Value']);
        $defaults = (new Arguments)->withFlags(['-F'])->withOptions(['Option' => 'Default']);
        $arguments = $arguments->withDefaults($defaults);

        static::assertSame('-F -A -o Option=Value', $arguments->getCliArguments());
    }

    /**
     * @dataProvider getArgumentStringDataProvider
     */
    public function testGetArgumentString($flags, $options, $expected)
    {
        $arguments = (new Arguments)->withFlags($flags)->withOptions($options);

        static::assertSame($expected, $arguments->getCliArguments());
    }

    public function getArgumentStringDataProvider()
    {
        return [
            [
                ['-A', '-F'],
                [],
                '-A -F'
            ],
            [
                ['-A', '-F'],
                ['Option' => 'Value'],
                '-A -F -o Option=Value'
            ],
            [
                ['-A', '-b' => 'somevalue'],
                ['Option' => 'Value'],
                '-A -b somevalue -o Option=Value'
            ]
        ];
    }

    public function testWithMultiplexing()
    {
        $host = new Host('test');
        $arguments = (new Arguments)->withMultiplexing($host);
        $controlPath = $arguments->getOption('ControlPath');

        static::assertEquals(
            "-o ControlMaster=auto -o ControlPersist=60 -o ControlPath=$controlPath",
            $arguments->getCliArguments()
        );
    }

    public function testCanOverrideMultiplexingOptions()
    {
        $host = new Host('test');
        $arguments = (new Arguments)->withOption('ControlPersist', '600')->withMultiplexing($host);
        $controlPath = $arguments->getOption('ControlPath');

        static::assertEquals(
            "-o ControlMaster=auto -o ControlPersist=600 -o ControlPath=$controlPath",
            $arguments->getCliArguments()
        );
    }

    public function testGetOption()
    {
        $arguments = (new Arguments)->withOption('ControlPersist', '600');

        static::assertEquals('600', $arguments->getOption('ControlPersist'));
    }

    public function testGetValue()
    {
        $arguments = (new Arguments)->withFlags(['-A', '-p' => null, '-b' => 'value']);

        static::assertTrue($arguments->getFlag('-A'));
        static::assertTrue($arguments->getFlag('-p'));
        static::assertEquals('value', $arguments->getFlag('-b'));
    }
}
