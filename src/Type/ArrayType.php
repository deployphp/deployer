<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Type;

/**
 * Class Array
 * @package Deployer\Type
 */
class ArrayType
{
    /**
     * @param array $a
     * @param array $b
     * @return array
     */
    public static function merge(array $original, array $override)
    {
        foreach ($override as $key => $value) {
            if (isset($original[$key])) {
                if (!is_array($original[$key])) {
                    $original[$key] = $value;
                } elseif (array_keys($original[$key]) === range(0, count($original[$key]) - 1)) {
                    $original[$key] = array_unique(array_merge($original[$key], $value));
                } else {
                    $original[$key] = ArrayType::merge($original[$key], $value);
                }
            } else {
                $original[$key] = $value;
            }
        }

        return $original;
    }
}
