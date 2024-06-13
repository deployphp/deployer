<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Importer;

use Deployer\Deployer;
use Deployer\Exception\ConfigurationException;
use Deployer\Exception\Exception;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Constraints\Factory;
use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
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
use function Deployer\Support\find_line_number;
use function Deployer\task;
use function Deployer\upload;
use function substr;
use const ARRAY_FILTER_USE_KEY;

class Importer
{
    /**
     * @var string
     */
    private static $recipeFilename;
    /**
     * @var string
     */
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
                self::$recipeSource = file_get_contents($path, true);
                $root = array_filter(Yaml::parse(self::$recipeSource), static function (string $key) {
                    return substr($key, 0, 1) !== '.';
                }, ARRAY_FILTER_USE_KEY);

                $schema = 'file://' . __DIR__ . '/../schema.json';
                if (Deployer::isPharArchive()) {
                    $schema = __DIR__ . '/../schema.json';
                }
                $yamlSchema = json_decode(file_get_contents($schema));
                $schemaStorage = new SchemaStorage();
                $schemaStorage->addSchema('file://schema', $yamlSchema);
                $validator = new Validator(new Factory($schemaStorage));
                $validator->validate($root, $yamlSchema, Constraint::CHECK_MODE_TYPE_CAST);
                if (!$validator->isValid()) {
                    $msg = "YAML " . self::$recipeFilename . " does not validate. Violations:\n";
                    foreach ($validator->getErrors() as $error) {
                        $msg .= "[{$error['property']}] {$error['message']}\n";
                    }
                    throw new ConfigurationException($msg);
                }

                foreach (array_keys($root) as $key) {
                    static::$key($root[$key]);
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
            $body = function () {
            };
            $task = task($name, $body);

            foreach ($steps as $step) {
                $buildStep = function ($step) use (&$body, $task) {
                    extract($step);

                    if (isset($cd)) {
                        $prev = $body;
                        $body = function () use ($cd, $prev) {
                            $prev();
                            cd($cd);
                        };
                    }

                    if (isset($run)) {
                        $has = 'run';
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
                        if (isset($has)) {
                            throw new ConfigurationException("Task step can not have both $has and run_locally.");
                        }
                        $has = 'run_locally';
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
                        if (isset($has)) {
                            throw new ConfigurationException("Task step can not have both $has and upload.");
                        }
                        $has = 'upload';
                        $prev = $body;
                        $body = function () use ($upload, $prev) {
                            $prev();
                            upload($upload['src'], $upload['dest']);
                        };
                    }

                    if (isset($download)) {
                        if (isset($has)) {
                            throw new ConfigurationException("Task step can not have both $has and downlaod.");
                        }
                        $has = 'downlaod';
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
