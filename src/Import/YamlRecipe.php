<?php

declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Import;

use Deployer\Exception\ConfigurationException;
use Deployer\Exception\Exception;
use Symfony\Component\Yaml\Yaml;

use function array_filter;
use function array_keys;
use function Deployer\after;
use function Deployer\before;
use function Deployer\cd;
use function Deployer\download;
use function Deployer\host;
use function Deployer\localhost;
use function Deployer\run;
use function Deployer\runLocally;
use function Deployer\set;
use function Deployer\task;
use function Deployer\upload;

use const ARRAY_FILTER_USE_KEY;

class YamlRecipe
{
    private static string $filename;

    public static function exec(string $path): void
    {
        self::$filename = basename($path);
        $content = file_get_contents($path, true);

        $root = array_filter(Yaml::parse($content), static function (string $key) {
            return !str_starts_with($key, '.');
        }, ARRAY_FILTER_USE_KEY);

        foreach (array_keys($root) as $key) {
            static::$key($root[$key]);
        }
    }

    protected static function import(mixed $paths): void
    {
        Import::import($paths);
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
                $buildStep = function (array $step) use (&$body, $task) {
                    $has = null;

                    if (isset($step['cd'])) {
                        $cd = $step['cd'];
                        $prev = $body;
                        $body = function () use ($cd, $prev) {
                            $prev();
                            cd($cd);
                        };
                    }

                    if (isset($step['run'])) {
                        $has = 'run';
                        $run = $step['run'];
                        $prev = $body;
                        $body = function () use ($run, $prev) {
                            $prev();
                            try {
                                run($run);
                            } catch (Exception $e) {
                                $e->setTaskFilename(self::$filename);
                                throw $e;
                            }
                        };
                    }

                    if (isset($step['run_locally'])) {
                        if ($has !== null) {
                            throw new ConfigurationException("Task step can not have both $has and run_locally.");
                        }
                        $has = 'run_locally';
                        $run_locally = $step['run_locally'];
                        $prev = $body;
                        $body = function () use ($run_locally, $prev) {
                            $prev();
                            try {
                                runLocally($run_locally);
                            } catch (Exception $e) {
                                $e->setTaskFilename(self::$filename);
                                throw $e;
                            }
                        };
                    }

                    if (isset($step['upload'])) {
                        if ($has !== null) {
                            throw new ConfigurationException("Task step can not have both $has and upload.");
                        }
                        $has = 'upload';
                        $upload = $step['upload'];
                        $prev = $body;
                        $body = function () use ($upload, $prev) {
                            $prev();
                            upload($upload['src'], $upload['dest']);
                        };
                    }

                    if (isset($step['download'])) {
                        if ($has !== null) {
                            throw new ConfigurationException("Task step can not have both $has and download.");
                        }
                        $prev = $body;
                        $download = $step['download'];
                        $body = function () use ($download, $prev) {
                            $prev();
                            download($download['src'], $download['dest']);
                        };
                    }

                    foreach (['desc', 'once', 'hidden', 'limit', 'select'] as $method) {
                        if (isset($step[$method])) {
                            $task->$method($step[$method]);
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
