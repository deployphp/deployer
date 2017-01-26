<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server;

interface RecursiveUploadEnabledInterface
{
    /**
     * Upload a directory to remote server recursively.
     * @param string $local Local path to directory.
     * @param string $remote Remote path where upload.
     */
    public function uploadDirectory($local, $remote);
}
