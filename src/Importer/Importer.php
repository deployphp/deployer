<?php declare(strict_types=1);
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
use function Deployer\cd;
use function Deployer\download;
use function Deployer\host;
use function Deployer\localhost;
use function Deployer\run;
use function Deployer\set;
use function Deployer\Support\find_line_number;
use function Deployer\task;
use function Deployer\upload;

class Importer
{
    private static $recipeFilename;
    private static $recipeSource;

    /**
     * @param string|string[] $paths
     */
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
                self::$recipeFilename = basename($path);
                self::$recipeSource = file_get_contents($path);
                $root = Yaml::parse(self::$recipeSource);
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

    protected static function hosts(array $hosts)
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

    protected static function config(array $config)
    {
        foreach ($config as $key => $value) {
            set($key, $value);
        }
    }

    protected static function tasks(array $tasks)
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
                $wrapRun = function ($cmd) {
                    try {
                        run($cmd);
                    } catch (Exception $e) {
                        $e->setTaskFilename(self::$recipeFilename);
                        $e->setTaskLineNumber(find_line_number(self::$recipeSource, $cmd));
                        throw $e;
                    }
                };
                $body = function () use ($wrapRun, $script) {
                    if (is_string($script)) {
                        $wrapRun($script);
                    } else {
                        foreach ($script as $line) {
                            if (preg_match('/^cd\s(?<path>.+)/i', $line, $matches)) {
                                cd($matches['path']);
                            } else {
                                $wrapRun($line);
                            }
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

    protected static function after(array $after)
    {
        foreach ($after as $key => $value) {
            if (is_array($value)) {
                foreach (array_reverse($value) as $v) {
                    after($key, $v);
                }
            } else {
                after($key, $value);
            }
        }
    }

    protected static function before(array $before)
    {
        foreach ($before as $key => $value) {
            if (is_array($value)) {
                foreach (array_reverse($value) as $v) {
                    before($key, $v);
                }
            } else {
                before($key, $value);
            }
        }
    }
}
