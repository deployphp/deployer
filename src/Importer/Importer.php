<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Importer;

use Deployer\Exception\Exception;
use Symfony\Component\Yaml\Yaml;
use function Deployer\after;
use function Deployer\before;
use function Deployer\download;
use function Deployer\host;
use function Deployer\localhost;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;
use function Deployer\upload;

class Importer
{
    public static function import($paths)
    {
        if (!is_array($paths)) {
            $paths = [$paths];
        }
        foreach ($paths as $path) {
            if (preg_match('/\.php$/i', $path)) {
                // Prevent variable leak into deploy.php file
                call_user_func(function () use ($path) {
                    // Reorder autoload stack
                    $originStack = spl_autoload_functions();

                    require $path;

                    $newStack = spl_autoload_functions();
                    if ($originStack[0] !== $newStack[0]) {
                        foreach (array_reverse($originStack) as $loader) {
                            spl_autoload_unregister($loader);
                            spl_autoload_register($loader, true, true);
                        }
                    }
                });
            } else if (preg_match('/\.ya?ml$/i', $path)) {
                $root = Yaml::parse(file_get_contents($path));
                foreach (array_keys($root) as $key) {
                    try {
                        self::$key($root[$key]);
                    } catch (\Throwable $exception) {
                        throw new Exception("Wrong syntax in \"$key:\" section.", 0, $exception);
                    }
                }
            } else {
                throw new Exception("Unknown file format: $path\nOnly .php and .yaml supported.");
            }
        }
    }

    protected static function hosts($hosts)
    {
        foreach ($hosts as $alias => $config) {
            if ($config['local'] ?? false) {
                $host = localhost($alias);
            } else {
                $host = host($alias);
            }
            if (is_array($config)) {
                foreach ($config as $key => $value) {
                    $host->set($key, $value);
                }
            }
        }
    }

    protected static function config($config)
    {
        foreach ($config as $key => $value) {
            set($key, $value);
        }
    }

    protected static function tasks($tasks)
    {
        $buildTask = function ($name, $config) {
            extract($config);

            $body = null;
            if (isset($script)) {
                if (!is_string($script)) {
                    foreach ($script as $line) {
                        if (!is_string($line)) {
                            throw new Exception("Script should be a string: $line");
                        }
                    }
                }
                $body = function () use ($script) {
                    if (is_string($script)) {
                        run($script);
                    } else {
                        foreach ($script as $line) {
                            run($line);
                        }
                    }
                };
            }
            if (isset($upload)) {
                if (!isset($upload['src']) || !isset($upload['dest'])) {
                    throw new Exception("Upload should have `src:` and `dest:` fields");
                }
                $prev = $body;
                $body = function () use ($upload, $prev) {
                    upload($upload['src'], $upload['dest']);
                    if (!empty($prev)) {
                        $prev();
                    }
                };
            }
            if (isset($download)) {
                if (!isset($download['src']) || !isset($download['dest'])) {
                    throw new Exception("Download should have `src:` and `dest:` fields");
                }
                $prev = $body;
                $body = function () use ($download, $prev) {
                    download($download['src'], $download['dest']);
                    if (!empty($prev)) {
                        $prev();
                    }
                };
            }
            $task = task($name, $body);

            $methods = [
                'desc',
                'local',
                'once',
                'hidden',
                'shallow',
                'limit',
                'select',
            ];

            foreach ($methods as $method) {
                if (isset($$method)) {
                    $task->$method($$method);
                }
            }
        };

        foreach ($tasks as $name => $config) {
            foreach ($config as $key => $value) {
                if (!is_int($key) || !is_string($value)) {
                    goto not_a_group_task;
                }
            }

            // Create a group task.
            task($name, $config);
            continue;

            not_a_group_task:
            $buildTask($name, $config);
        }
    }

    protected static function after($after)
    {
        foreach ($after as $key => $value) {
            after($key, $value);
        }
    }

    protected static function before($before)
    {
        foreach ($before as $key => $value) {
            before($key, $value);
        }
    }
}
