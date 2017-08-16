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
    const ENDPOINT = 'https://requestb.in/r9z34wr9';

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
            try {
                Httpie::post(self::ENDPOINT)->body($stats)->send();
            } catch (\Throwable $e) {
                // pass
            }
        } elseif ($pid === 0) {
            // Child process.
            posix_setsid();
            try {
                Httpie::post(self::ENDPOINT)->body($stats)->send();
            } catch (\Throwable $e) {
                // pass
            }
            // Close child process after doing job.
            exit(0);
        }
    }
}
