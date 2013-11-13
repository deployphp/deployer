<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Remote;

interface RemoteInterface 
{
    public function cd($directory);
    public function execute($command);
    public function uploadFile($from, $to);
} 