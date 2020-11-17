<?php declare(strict_types=1);
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
 */
function str_contains(string $haystack, string $needle): bool
{
    return strpos($haystack, $needle) !== false;
}

/**
 * Checks if string stars with given prefix.
 */
function starts_with(string $string, string $startString): bool
{
    $len = strlen($startString);
    return (substr($string, 0, $len) === $startString);
}

/**
 * This function used for create environment string.
 */
function env_stringify(array $array): string
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
 * @param mixed $var
 */
function is_closure($var): bool
{
    return is_object($var) && ($var instanceof \Closure);
}

/**
 * Check if all elements satisfy predicate.
 */
function array_all(array $array, callable $predicate): bool
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
 */
function normalize_line_endings(string $string): string
{
    return str_replace(["\r\n", "\r"], "\n", $string);
}

/**
 * Expand leading tilde (~) symbol in given path.
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

function fork(callable $callable)
{
    $pid = null;
    // Make sure function is not disabled via php.ini "disable_functions"
    if (extension_loaded('pcntl') && function_exists('pcntl_fork')) {
        declare(ticks=1);
        $pid = pcntl_fork();
    }
    if (is_null($pid) || $pid === -1) {
        // Fork fails or there is no `pcntl` extension.
        $callable();
    } elseif ($pid === 0) {
        // Child process.
        posix_setsid();
        $callable();
        exit(0);
    }
}

function find_line_number(string $source, string $string): int
{
    $string = explode(PHP_EOL, $string)[0];
    $before = strstr($source, $string, true);
    if (false !== $before) {
        return count(explode(PHP_EOL, $before));
    }
    return 1;
}

function find_config_line(string $source, string $name): \Generator
{
    foreach (explode(PHP_EOL, $source) as $n => $line) {
        if (preg_match("/\(['\"]{$name}['\"]/", $line)) {
            yield [$n + 1, $line];
        }
        if (preg_match("/\s{$name}:/", $line)) {
            yield [$n + 1, $line];
        }
    }
}
