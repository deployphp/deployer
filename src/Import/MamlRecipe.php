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
use Deployer\Exception\SchemaException;
use Maml\Ast\ArrayNode;
use Maml\Ast\BooleanNode;
use Maml\Ast\ObjectNode;
use Maml\Ast\Property;
use Maml\Ast\RawStringNode;
use Maml\Ast\Span;
use Maml\Ast\StringNode;
use Maml\Maml;
use Maml\Schema\S;
use Maml\Schema\SchemaType;
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

class MamlRecipe
{
    private string $filename;
    private string $content;

    public function __construct(string $path)
    {
        $this->filename = basename($path);
        $this->content = file_get_contents($path, true);
    }

    public function run(): void
    {
        $recipe = Maml::parseAst($this->content);

        $validationErrors = Maml::validate($recipe, $this->schema());

        $exception = null;
        foreach ($validationErrors as $error) {
            $exception = new SchemaException(Maml::errorSnippet(
                $this->content,
                $error->span,
                $error->message,
                context: 3,
                gutter: true,
            ));
        }
        if ($exception) {
            throw $exception;
        }

        foreach ($recipe->value->properties as $property) {
            $key = $property->key->value;
            switch ($key) {
                case 'import':
                    $this->import($property);
                    break;

                case 'config':
                    $this->config($property);
                    break;

                case 'hosts':
                    $this->hosts($property);
                    break;

                case 'tasks':
                    $this->tasks($property);
                    break;

                default:
                    $this->throwException("Unknown property $key", $property->key->span);
            }
        }
    }

    private function schema(): SchemaType
    {
        $step = S::object([
            'cd' => S::optional(S::string()),
            'run' => S::optional(S::string()),
            'run_locally' => S::optional(S::string()),
            'upload' => S::optional(S::object([
                'src' => S::union(S::string(), S::arrayOf(S::string())),
                'dest' => S::string(),
            ])),
            'download' => S::optional(S::object([
                'src' => S::string(),
                'dest' => S::string(),
            ])),
            'desc' => S::optional(S::string()),
            'once' => S::optional(S::boolean()),
            'hidden' => S::optional(S::boolean()),
            'limit' => S::optional(S::number()),
            'select' => S::optional(S::string()),
        ]);

        return S::object([
            'import' => S::optional(
                S::union(
                    S::string(),
                    S::arrayOf(S::string()),
                ),
            ),
            'config' => S::optional(
                S::map(S::any()),
            ),
            'hosts' => S::optional(
                S::map(
                    S::map(S::any()),
                ),
            ),
            'tasks' => S::optional(
                S::map(
                    S::union(
                        S::arrayOf($step),
                        S::arrayOf(S::string()),
                    ),
                ),
            ),
            'before' => S::optional(
                S::map(S::string()),
            ),
            'after' => S::optional(
                S::map(S::string()),
            ),
        ]);
    }

    private function import(Property $property): void
    {
        if ($property->value instanceof StringNode || $property->value instanceof RawStringNode) {
            Import::import($property->value->value);
        } elseif ($property->value instanceof ArrayNode) {
            foreach ($property->value->elements as $element) {
                $import = $element->value;
                if ($import instanceof StringNode || $import instanceof RawStringNode) {
                    Import::import($import->value);
                } else {
                    $this->throwException('Invalid import format', $import->span);
                }
            }
        } else {
            $this->throwException('Invalid import format', $property->value->span);
        }
    }

    protected function config(Property $property): void
    {
        $object = $property->value;
        if (!$object instanceof ObjectNode) {
            $this->throwException('Invalid config format', $property->value->span);
        }
        foreach ($object->properties as $property) {
            $key = $property->key->value;
            $value = $property->value;
            set($key, Maml::toValue($value));
        }
    }

    protected function hosts(Property $property): void
    {
        $object = $property->value;
        if (!$object instanceof ObjectNode) {
            $this->throwException('Invalid hosts format', $property->value->span);
        }
        foreach ($object->properties as $property) {
            $alias = $property->key->value;
            $object = $property->value;
            if (!$object instanceof ObjectNode) {
                $this->throwException('Invalid host format', $property->value->span);
            }
            $isLocalhost = false;
            foreach ($object->properties as $config) {
                $key = $config->key->value;
                $value = $config->value;
                if ($key === 'local' && $value instanceof BooleanNode && $value->value === true) {
                    $isLocalhost = true;
                }
            }
            if ($isLocalhost) {
                $host = localhost($alias);
            } else {
                $host = host($alias);
            }
            foreach ($object->properties as $config) {
                $host->set($config->key->value, Maml::toValue($config->value));
            }
        }
    }

    protected function tasks(Property $property): void
    {
        $tasks = $property->value;
        if (!$tasks instanceof ObjectNode) {
            $this->throwException('Invalid tasks format', $property->value->span);
        }

        foreach ($tasks->properties as $task) {
            $name = $task->key->value;
            $value = $task->value;
            $desc = trim(implode('\n', array_map(fn($comment) => $comment->value, $task->leadingComments)));
            if (!$value instanceof ArrayNode) {
                $this->throwException('Task must be an array', $value->span);
            }
            $this->createTask($name, $value, $desc);
        }
    }

    private function createTask(string $name, ArrayNode $array, string $desc)
    {
        $isGroupTask = true;
        $groupTasks = [];
        foreach ($array->elements as $element) {
            if ($element->value instanceof StringNode || $element->value instanceof RawStringNode) {
                $groupTasks[] = $element->value->value;
            } else {
                $isGroupTask = false;
            }
        }

        if ($isGroupTask) {
            task($name, $groupTasks)->desc($desc);
            return;
        }

        $body = function () {
            // Empty task body.
        };

        foreach ($array->elements as $element) {
            $step = $element->value;
            if (!$step instanceof ObjectNode) {
                $this->throwException('Task step must be an object', $step->span);
            }

            foreach ($step->properties as $property) {
                $key = $property->key->value;
                $value = $property->value;

                $str = null;
                if ($value instanceof StringNode || $value instanceof RawStringNode) {
                    $str = $value->value;
                } else {
                    $this->throwException('Task step value must be a string', $value->span);
                }

                $prev = $body;

                switch ($key) {
                    case 'cd':
                        $body = function () use ($str, $prev, $property) {
                            $prev();
                            try {
                                cd($str);
                            } catch (\Throwable $e) {
                                $this->wrapException($e, $property->span);
                            }
                        };
                        break;

                    case 'run':
                        $body = function () use ($str, $prev, $property) {
                            $prev();
                            try {
                                run($str);
                            } catch (\Throwable $e) {
                                $this->wrapException($e, $property->span);
                            }
                        };
                        break;

                    case 'run_locally':
                        $body = function () use ($str, $prev, $property) {
                            $prev();
                            try {
                                runLocally($str);
                            } catch (\Throwable $e) {
                                $this->wrapException($e, $property->span);
                            }
                        };
                        break;

                    default:
                        $this->throwException("Unknown task step $key", $property->key->span);
                }
            }
        }

        task($name, $body)->desc($desc);
    }

    protected function tasks1(Property $property): void
    {
        $buildTask = function ($name, $steps) {
            $body = function () {
            };
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
                                $e->setTaskFilename($this->filename);
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
                                $e->setTaskFilename($this->filename);
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

    protected
    function after(array $after)
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

    protected
    function before(array $before)
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

    private function throwException(string $string, Span $span): never
    {
        throw new Exception(Maml::errorSnippet($this->content, $span, $string));
    }

    private function wrapException(\Throwable $e, Span $span): never
    {
        if ($e instanceof Exception) {
            $e->setTaskFilename($this->filename);
            $e->setTaskLineNumber($span->start->line);
        }
        throw $e;
    }
}
