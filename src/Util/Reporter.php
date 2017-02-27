<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Util;

class Reporter
{
    const ENDPOINT = 'https://deployer.org/api/stats';

    /**
     * @param array $stats
     */
    public static function report(array $stats)
    {
        $pid = null;
        if (extension_loaded('pcntl')) {
            declare(ticks = 1);
            $pid = pcntl_fork();
        }

        if (is_null($pid) || $pid === -1) {
            // Fork fails or there is no `pcntl` extension.
            self::send($stats);
        } elseif ($pid === 0) {
            // Child process.
            posix_setsid();
            self::send($stats);
            // Close child process after doing job.
            exit(0);
        }
    }

    /**
     * @param array $stats
     */
    private static function send(array $stats)
    {
        if (extension_loaded('curl')) {
            $body = json_encode($stats, JSON_PRETTY_PRINT);
            $ch = curl_init(self::ENDPOINT);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($body)
            ]);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_exec($ch);
        } else {
            file_get_contents(self::ENDPOINT . '?' . http_build_query($stats));
        }
    }
}
