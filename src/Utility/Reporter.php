<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Utility;

/**
 * @codeCoverageIgnore
 */
class Reporter
{
    const ENDPOINT = 'https://deployer.org/api/stats';

    public static function report(array $stats)
    {
        $pid = null;
        // make sure function is not disabled via php.ini "disable_functions"
        if (extension_loaded('pcntl') && function_exists('pcntl_fork')) {
            declare(ticks = 1);
            $pid = pcntl_fork();
        }

        if (is_null($pid) || $pid === -1) {
            // Fork fails or there is no `pcntl` extension.
            try {
                self::send($stats);
            } catch (\Throwable $e) {
                // pass
            }
        } elseif ($pid === 0) {
            // Child process.
            posix_setsid();
            try {
                self::send($stats);
            } catch (\Throwable $e) {
                // pass
            }
            // Close child process after doing job.
            exit(0);
        }
    }

    private static function send(array $stats)
    {
        Httpie::post(self::ENDPOINT)
            ->body($stats)
            ->setopt(CURLOPT_SSL_VERIFYPEER, false)
            ->send();
    }
}
