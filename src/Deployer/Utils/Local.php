<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Utils;

class Local 
{
    public function execute($command)
    {
        $descriptors = array(
            0 => array("pipe", "r"), // stdin - read channel
            1 => array("pipe", "w"), // stdout - write channel
            2 => array("pipe", "w"), // stdout - error channel
            3 => array("pipe", "r"), // stdin
        );

        $process = proc_open($command, $descriptors, $pipes);

        if (!is_resource($process)) {
            die("Can't open resource with proc_open.");
        }

        // Nothing to push to input.
        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $error = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        fclose($pipes[3]);

        // Close all pipes before proc_close!
        $code = proc_close($process);

        return $output;
    }
} 