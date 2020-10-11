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
 * Checks if string stars with given prefix.
 *
 * @param string $string
 * @param string $startString
 * @return bool
 */
function starts_with(string $string, string $startString)
{
    $len = strlen($startString);
    return (substr($string, 0, $len) === $startString);
}

/**
 * This function used for create environment string.
 */
function env_strinfigy(array $array): string
{
    return implode(' ', array_map(
        function ($key, $value) {
            return sprintf("%s='%s'", $key, $value);
        },
        array_keys($array),
        $array
    ));
}

/**
 * Check if var is closure.
 *
 * @param $var
 * @return bool
 */
function is_closure($var)
{
    return is_object($var) && ($var instanceof \Closure);
}

/**
 * Check if all elements satisfy predicate.
 *
 * @param array $array
 * @param \Closure $predicate
 * @return bool
 */
function array_all(array $array, $predicate)
{
    foreach ($array as $key => $value) {
        if (!$predicate($value, $key)) {
            return false;
        }
    }
    return true;
}

/**
 * Cleanup CRLF new line endings.
 * Issue #2111
 *
 * @param $string
 * @return string
 */
function normalize_line_endings($string)
{
    return str_replace(["\r\n", "\r"], "\n", $string);
}

/**
 * Expand leading tilde (~) symbol in given path.
 *
 * @param string $path
 * @return string
 */
function parse_home_dir(string $path): string
{
    if ('~' === $path || 0 === strpos($path, '~/')) {
        if (isset($_SERVER['HOME'])) {
            $home = $_SERVER['HOME'];
        } elseif (isset($_SERVER['HOMEDRIVE'], $_SERVER['HOMEPATH'])) {
            $home = $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'];
        } else {
            return $path;
        }

        return $home . substr($path, 1);
    }

    return $path;
}
