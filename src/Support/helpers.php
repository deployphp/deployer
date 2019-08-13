<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Support;

/**
 * Flatten array
 *
 * @param array $array
 * @return array
 */
function array_flatten(array $array)
{
    $flatten = [];
    array_walk_recursive($array, function ($value) use (&$flatten) {
        $flatten[] = $value;
    });
    return $flatten;
}

/**
 * Recursively merge two config arrays with a specific behavior:
 *
 * 1. scalar values are overridden
 * 2. array values are extended uniquely if all keys are numeric
 * 3. all other array values are merged
 *
 * @param array $original
 * @param array $override
 * @return array
 * @see http://stackoverflow.com/a/36366886/6812729
 */
function array_merge_alternate(array $original, array $override)
{
    foreach ($override as $key => $value) {
        if (isset($original[$key])) {
            if (!is_array($original[$key])) {
                if (is_numeric($key)) {
                    // Append scalar value
                    $original[] = $value;
                } else {
                    // Override scalar value
                    $original[$key] = $value;
                }
            } elseif (array_keys($original[$key]) === range(0, count($original[$key]) - 1)) {
                // Uniquely append to array with numeric keys
                $original[$key] = array_unique(array_merge($original[$key], $value));
            } else {
                // Merge all other arrays
                $original[$key] = array_merge_alternate($original[$key], $value);
            }
        } else {
            // Simply add new key/value
            $original[$key] = $value;
        }
    }

    return $original;
}

/**
 * Determines if the given string contains the given value.
 *
 * @param string $haystack
 * @param string $needle
 * @return bool
 */
function str_contains(string $haystack, string $needle)
{
    return strpos($haystack, $needle) !== false;
}

/**
 * Take array of key/value and create string of it.
 *
 * This function used for create environment string.
 *
 * @param array $array
 *
 * @return string
 */
function array_to_string(array $array): string
{
    return implode(' ', array_map(
        function ($key, $value) {
            return sprintf("%s='%s'", $key, $value);
        },
        array_keys($array),
        $array
    ));
}
