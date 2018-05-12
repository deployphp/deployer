<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Type;

/**
 * Class Csv
 * @package Deployer\Type
 */
class Csv
{
    /**
     * @param string $input
     * @return array
     */
    public static function parse($input)
    {
        return array_map('str_getcsv', explode("\n", $input));
    }
}
