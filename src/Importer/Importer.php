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
use function Deployer\runLocally;
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
        $buildTask = function ($name, $steps) {
            $body = function () {};
            $task = task($name, $body);

            foreach ($steps as $step) {
                $buildStep = function ($step) use (&$body, $task) {
                    extract($step);

                    if (isset($cd)) {
                        if (!is_string($cd)) {
                            throw new Exception("The \"cd\" should be a string.");
                        }
                        $prev = $body;
                        $body = function () use ($cd, $prev) {
                            $prev();
                            cd($cd);
                        };
                    }
                    if (isset($run)) {
                        if (!is_string($run)) {
                            throw new Exception("The \"run\" should be a string.");
                        }
                        $prev = $body;
                        $body = function () use ($run, $prev) {
                            $prev();
                            try {
                                run($run);
                            } catch (Exception $e) {
                                $e->setTaskFilename(self::$recipeFilename);
                                $e->setTaskLineNumber(find_line_number(self::$recipeSource, $run));
                                throw $e;
                            }
                        };
                    }
                    if (isset($run_locally)) {
                        if (!is_string($run_locally)) {
                            throw new Exception("The \"run_locally\" should be a string.");
                        }
                        $prev = $body;
                        $body = function () use ($run_locally, $prev) {
                            $prev();
                            try {
                                runLocally($run_locally);
                            } catch (Exception $e) {
                                $e->setTaskFilename(self::$recipeFilename);
                                $e->setTaskLineNumber(find_line_number(self::$recipeSource, $run_locally));
                                throw $e;
                            }
                        };
                    }
                    if (isset($upload)) {
                        if (!isset($upload['src']) || !isset($upload['dest'])) {
                            throw new Exception("Upload should have `src:` and `dest:` fields");
                        }
                        $prev = $body;
                        $body = function () use ($upload, $prev) {
                            $prev();
                            upload($upload['src'], $upload['dest']);
                        };
                    }
                    if (isset($download)) {
                        if (!isset($download['src']) || !isset($download['dest'])) {
                            throw new Exception("Download should have `src:` and `dest:` fields");
                        }
                        $prev = $body;
                        $body = function () use ($download, $prev) {
                            $prev();
                            download($download['src'], $download['dest']);
                        };
                    }

                    $methods = [
                        'desc',
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

                $buildStep($step);
                $task->setCallback($body);
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
