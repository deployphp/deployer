<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Support;

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function testArrayFlatten()
    {
        self::assertEquals(['a', 'b', 'c'], array_flatten(['a', ['b', 'key' => ['c']]]));
    }

    public function testArrayMergeAlternate()
    {
        $config = [
            'one',
            'two' => 2,
            'nested' => [],
        ];

        $config = array_merge_alternate($config, [
            'two' => 20,
            'nested' => [
                'first',
            ],
        ]);

        $config = array_merge_alternate($config, [
            'nested' => [
                'second',
            ],
        ]);

        $config = array_merge_alternate($config, [
            'extra'
        ]);

        self::assertEquals([
            'one',
            'two' => 20,
            'nested' => [
                'first',
                'second',
            ],
            'extra',
        ], $config);
    }

    public function testParseHomeDir()
    {
        $this->assertStringStartsWith('/', parse_home_dir('~/path'));
        $this->assertStringStartsWith('/', parse_home_dir('~'));
        $this->assertStringStartsWith('~', parse_home_dir('~path'));
        $this->assertStringEndsWith('~', parse_home_dir('path~'));
    }

    public function testEscapeShellArgument()
    {
        $this->assertEquals('\'{"foobar":"Lorem ipsum\'\\\'\'s dolor"}\'', escape_shell_argument(json_encode(['foobar' => 'Lorem ipsum\'s dolor'])));
    }
}
