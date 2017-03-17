<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Host;

class Range
{
    const PATTERN = '/\[(.+?)\]/';

    public static function expand(array $hostnames)
    {
        $expanded = [];
        foreach ($hostnames as $hostname) {
            if (preg_match(self::PATTERN, $hostname, $matches)) {
                list($start, $end) = explode(':', $matches[1]);
                $zeroBased = preg_match('/^0[1-9]/', $start);

                foreach (range($start, $end) as $i) {
                    $expanded[] = preg_replace(self::PATTERN, self::format($i, $zeroBased), $hostname);
                }
            } else {
                $expanded[] = $hostname;
            }
        }

        return $expanded;
    }

    private static function format($i, $zeroBased)
    {
        if ($zeroBased) {
            return strlen($i) === 1 ? "0$i" : $i;
        } else {
            return $i;
        }
    }
}
