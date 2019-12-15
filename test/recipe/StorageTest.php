<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

class StorageTest extends DepCase
{
    protected function load()
    {
        require DEPLOYER_FIXTURES . '/recipe/storage.php';
    }

    public function testStorage()
    {
        self::markTestSkipped('TODO: This test should be fixed in future.');
        $output = $this->start('test', [
            '--parallel' => true,
            '--file' => DEPLOYER_FIXTURES . '/recipe/storage.php'
        ]);

        self::assertStringContainsString('a:a', $output);
        self::assertStringContainsString('b:b', $output);
        self::assertStringContainsString('f:f', $output);
        self::assertStringContainsString('abcdef', $output);
    }
}
