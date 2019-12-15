<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

class PartyTest extends DepCase
{
    protected function load()
    {
        require DEPLOYER_FIXTURES . '/recipe/party.php';
    }

    protected function setUp(): void
    {
        self::$currentPath = self::$tmpPath . '/localhost';
    }

    public function testEnvironment()
    {
        $output = $this->start('test_env');
        self::assertStringContainsString('env value ext', $output);
        self::assertStringContainsString('env value local', $output);
    }

    public function testInvoke()
    {
        $output = $this->start('test_invoke');
        self::assertStringContainsString('first', $output);
        self::assertStringContainsString('second', $output);
    }

    public function testInvokeGroup()
    {
        $output = $this->start('test_invoke_group');
        self::assertStringContainsString('first', $output);
        self::assertStringContainsString('second', $output);
    }

    public function testOn()
    {
        $output = $this->start('test_on');
        self::assertStringContainsString(
            "<yes:test_on01>\n" .
            "<yes:test_on02>\n" .
            "<yes:test_on03>\n" .
            "<yes:test_on04>\n" .
            "<yes:test_on05>\n",
            $output);
    }
}
