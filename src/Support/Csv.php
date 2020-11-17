<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Support;

/**
 * Class Csv
 * @package Deployer\Type
 */
class Csv
{
    public static function parse(string $input): array
    {
        return array_map('str_getcsv', explode("\n", $input));
    }
}
