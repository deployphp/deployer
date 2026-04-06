<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class QuoteTest extends TestCase
{
    public function testEmptyString()
    {
        self::assertEquals("\$''", quote(''));
    }

    #[DataProvider('safeStringsProvider')]
    public function testSafeStringsPassThrough(string $input)
    {
        self::assertEquals($input, quote($input));
    }

    public static function safeStringsProvider(): array
    {
        return [
            'simple word' => ['hello'],
            'path' => ['/usr/local/bin'],
            'dotfile' => ['.env'],
            'with dash' => ['my-app'],
            'with plus' => ['c++'],
            'with at' => ['user@host'],
            'with colon' => ['host:port'],
            'with equals' => ['key=value'],
            'with comma' => ['a,b'],
            'with percent' => ['100%'],
            'mixed safe chars' => ['/home/user/.config/my-app@2.0:main,alt+debug=1'],
            'digits' => ['12345'],
            'underscore' => ['foo_bar'],
        ];
    }

    #[DataProvider('unsafeStringsProvider')]
    public function testUnsafeStrings(string $input, string $expected)
    {
        self::assertEquals($expected, quote($input));
    }

    public static function unsafeStringsProvider(): array
    {
        return [
            'space' => ['hello world', "\$'hello world'"],
            'single quote' => ["it's", "\$'it\\'s'"],
            'double quote' => ['say "hi"', "\$'say \"hi\"'"],
            'backslash' => ['back\\slash', "\$'back\\\\slash'"],
            'newline' => ["line1\nline2", "\$'line1\\nline2'"],
            'tab' => ["col1\tcol2", "\$'col1\\tcol2'"],
            'carriage return' => ["line1\rline2", "\$'line1\\rline2'"],
            'form feed' => ["page\fbreak", "\$'page\\fbreak'"],
            'vertical tab' => ["vert\vtab", "\$'vert\\vtab'"],
            'null byte' => ["null\0byte", "\$'null\\0byte'"],
            'semicolon' => ['cmd; rm -rf /', "\$'cmd; rm -rf /'"],
            'pipe' => ['a | b', "\$'a | b'"],
            'ampersand' => ['a & b', "\$'a & b'"],
            'dollar' => ['$HOME', "\$'\$HOME'"],
            'backtick' => ['`whoami`', "\$'`whoami`'"],
            'parens' => ['$(cmd)', "\$'\$(cmd)'"],
            'glob star' => ['*.txt', "\$'*.txt'"],
            'question mark' => ['file?.txt', "\$'file?.txt'"],
            'brackets' => ['[abc]', "\$'[abc]'"],
            'curly braces' => ['{a,b}', "\$'{a,b}'"],
            'hash' => ['#comment', "\$'#comment'"],
            'exclamation' => ['!event', "\$'!event'"],
            'tilde' => ['~user', "\$'~user'"],
            'angle brackets' => ['a > b < c', "\$'a > b < c'"],
        ];
    }

    public function testMultipleEscapes()
    {
        self::assertEquals("\$'it\\'s a\\nnew\\\\line'", quote("it's a\nnew\\line"));
    }

    public function testAllSpecialCharsAtOnce()
    {
        $input = "'\\\f\n\r\t\v\0";
        $expected = "\$'\\'\\\\\\f\\n\\r\\t\\v\\0'";
        self::assertEquals($expected, quote($input));
    }

    public function testUnicodeContent()
    {
        self::assertEquals("\$'héllo wörld'", quote('héllo wörld'));
    }

    public function testJsonString()
    {
        $json = json_encode(['foo' => "bar's"]);
        self::assertEquals("\$'{\"foo\":\"bar\\'s\"}'", quote($json));
    }

    public function testSingleCharSafe()
    {
        self::assertEquals('a', quote('a'));
    }

    public function testSingleCharUnsafe()
    {
        self::assertEquals("\$' '", quote(' '));
    }

    public function testOnlyBackslashes()
    {
        self::assertEquals("\$'\\\\\\\\'", quote('\\\\'));
    }

    public function testOnlyQuotes()
    {
        self::assertEquals("\$'\\'\\'\\''", quote("'''"));
    }

    public function testShellInjectionAttempts()
    {
        self::assertEquals("\$'; DROP TABLE users;--'", quote('; DROP TABLE users;--'));
        self::assertEquals("\$'`rm -rf /`'", quote('`rm -rf /`'));
        self::assertEquals("\$'\$(cat /etc/passwd)'", quote('$(cat /etc/passwd)'));
    }
}
