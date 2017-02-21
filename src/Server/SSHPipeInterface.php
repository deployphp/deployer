<?php
/* (c) Anton Medvedev <anton@medv.io>, Maxim Kuznetsov <skypluseg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server;

interface SSHPipeInterface
{
    /**
     * Create if it isn't created before an ssh connection to a server and
     * pipe it to shell including standard I/O streams.
     *
     * @param string|null $initialCommand Command which will be run right after ssh connection.
     */
    public function createSshPipe($initialCommand = null);
}