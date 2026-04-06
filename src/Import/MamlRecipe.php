<?php

declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Import;

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
use function Deployer\fail;
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

    public static function schema(): SchemaType
    {
        $cd = S::object([
            'cd' => S::string(),
        ]);

        $run = S::object([
            'run' => S::string(),
            'cd' => S::optional(S::string()),
            'cwd' => S::optional(S::string()),
            'env' => S::optional(S::map(S::string())),
            'secrets' => S::optional(S::map(S::string())),
            'nothrow' => S::optional(S::boolean()),
            'forceOutput' => S::optional(S::boolean()),
            'timeout' => S::optional(S::number()),
            'idleTimeout' => S::optional(S::number()),
        ]);

        $runLocally = S::object([
            'run_locally' => S::string(),
            'cwd' => S::optional(S::string()),
            'timeout' => S::optional(S::number()),
            'idleTimeout' => S::optional(S::number()),
            'secrets' => S::optional(S::map(S::string())),
            'env' => S::optional(S::map(S::string())),
            'nothrow' => S::optional(S::boolean()),
            'forceOutput' => S::optional(S::boolean()),
            'shell' => S::optional(S::string()),
        ]);

        $upload = S::object([
            'upload' => S::object([
                'src' => S::union(S::string(), S::arrayOf(S::string())),
                'dest' => S::string(),
            ]),
        ]);

        $download = S::object([
            'download' => S::object([
                'src' => S::string(),
                'dest' => S::string(),
            ]),
        ]);

        $taskConfig = S::object([
            'desc' => S::optional(S::string()),
            'once' => S::optional(S::boolean()),
            'hidden' => S::optional(S::boolean()),
            'limit' => S::optional(S::number()),
            'select' => S::optional(S::string()),
        ]);

        $step = S::union(
            $cd,
            $run,
            $runLocally,
            $upload,
            $download,
            $taskConfig,
        );

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
                S::map(
                    S::union(
                        S::string(),
                        S::arrayOf(S::string()),
                    ),
                ),
            ),
            'after' => S::optional(
                S::map(
                    S::union(
                        S::string(),
                        S::arrayOf(S::string()),
                    ),
                ),
            ),
            'fail' => S::optional(
                S::map(S::string()),
            ),
        ]);
    }

    public function run(): void
    {
        $recipe = Maml::parseAst($this->content);

        $validationErrors = Maml::validate($recipe, self::schema());

        $exception = null;
        foreach ($validationErrors as $error) {
            $exception = new SchemaException(
                Maml::errorSnippet(
                    $this->content,
                    $error->span,
                    $error->message,
                    context: 3,
                    gutter: true,
                ),
                $exception,
            );
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

                case 'before':
                    $this->before($property);
                    break;

                case 'after':
                    $this->after($property);
                    break;

                case 'fail':
                    $this->fail($property);
                    break;

                default:
                    $this->throwException("Unknown property $key", $property->key->span);
            }
        }
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
            $desc = trim(implode("\n", array_map(fn($comment) => $comment->value, $task->leadingComments)));
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

        $task = task($name, $body)->desc($desc);

        foreach ($array->elements as $element) {
            $object = $element->value;
            if (!$object instanceof ObjectNode) {
                $this->throwException('Task step must be an object', $object->span);
            }

            $step = Maml::toValue($element->value);

            foreach ($object->properties as $property) {
                $key = $property->key->value;

                if (in_array($key, ['desc', 'once', 'hidden', 'limit', 'select'])) {
                    $task->$key($step[$key]);
                    continue;
                }

                $prev = $body;

                $body = match ($key) {
                    'cd' => function () use ($step, $prev, $property) {
                        $prev();
                        try {
                            cd($step['cd']);
                        } catch (\Throwable $e) {
                            $this->wrapException($e, $property->span);
                        }
                    },
                    'run' => function () use ($step, $prev, $property) {
                        $prev();
                        try {
                            run(
                                $step['run'],
                                cwd: $step['cwd'] ?? null,
                                env: $step['env'] ?? null,
                                secrets: $step['secrets'] ?? null,
                                nothrow: $step['nothrow'] ?? false,
                                forceOutput: $step['forceOutput'] ?? false,
                                timeout: $step['timeout'] ?? null,
                                idleTimeout: $step['idleTimeout'] ?? null,
                            );
                        } catch (\Throwable $e) {
                            $this->wrapException($e, $property->span);
                        }
                    },
                    'run_locally' => function () use ($step, $prev, $property) {
                        $prev();
                        try {
                            runLocally(
                                $step['run_locally'],
                                cwd: $step['cwd'] ?? null,
                                timeout: $step['timeout'] ?? null,
                                idleTimeout: $step['idleTimeout'] ?? null,
                                secrets: $step['secrets'] ?? null,
                                env: $step['env'] ?? null,
                                forceOutput: $step['forceOutput'] ?? false,
                                nothrow: $step['nothrow'] ?? false,
                                shell: $step['shell'] ?? null,
                            );
                        } catch (\Throwable $e) {
                            $this->wrapException($e, $property->span);
                        }
                    },
                    'upload' => function () use ($step, $prev, $property) {
                        $prev();
                        try {
                            upload(
                                $step['upload']['src'],
                                $step['upload']['dest'],
                            );
                        } catch (\Throwable $e) {
                            $this->wrapException($e, $property->span);
                        }
                    },
                    'download' => function () use ($step, $prev, $property) {
                        $prev();
                        try {
                            download(
                                $step['download']['src'],
                                $step['download']['dest'],
                            );
                        } catch (\Throwable $e) {
                            $this->wrapException($e, $property->span);
                        }
                    },
                    default => $body,
                };
            }
        }

        $task->setCallback($body);

        return $task;
    }

    protected function before(Property $property): void
    {
        $object = $property->value;
        if (!$object instanceof ObjectNode) {
            $this->throwException('Invalid before format', $object->span);
        }
        foreach ($object->properties as $property) {
            $key = $property->key->value;
            $value = Maml::toValue($property->value);

            if (is_array($value)) {
                foreach (array_reverse($value) as $v) {
                    before($key, $v);
                }
            } else {
                before($key, $value);
            }
        }
    }

    protected function after(Property $property): void
    {
        $object = $property->value;
        if (!$object instanceof ObjectNode) {
            $this->throwException('Invalid after format', $object->span);
        }
        foreach ($object->properties as $property) {
            $key = $property->key->value;
            $value = Maml::toValue($property->value);

            if (is_array($value)) {
                foreach (array_reverse($value) as $v) {
                    after($key, $v);
                }
            } else {
                after($key, $value);
            }
        }
    }

    protected function fail(Property $property): void
    {
        $object = $property->value;
        if (!$object instanceof ObjectNode) {
            $this->throwException('Invalid fail format', $object->span);
        }
        foreach ($object->properties as $property) {
            $key = $property->key->value;
            $value = Maml::toValue($property->value);
            fail($key, $value);
        }
    }

    private function throwException(string $string, Span $span): never
    {
        throw new Exception(Maml::errorSnippet(
            $this->content,
            $span,
            $string,
            context: 3,
            gutter: true,
        ));
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
