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
        $output = $this->start('test', [
            '--parallel' => true,
            '--file' => DEPLOYER_FIXTURES . '/recipe/storage.php'
        ]);

        self::assertContains('a:a', $output);
        self::assertContains('b:b', $output);
        self::assertContains('f:f', $output);
        self::assertContains('abcdef', $output);
    }
}
