<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Documentation;


use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;

class DocGen
{
    public $root;
    /**
     * @var DocRecipe[]
     */
    public $recipes = [];

    public function __construct(string $root)
    {
        $this->root = str_replace(DIRECTORY_SEPARATOR, '/', realpath($root));
    }

    public function parse(string $source): void
    {
        $directory = new RecursiveDirectoryIterator($source);
        $iterator = new RegexIterator(new RecursiveIteratorIterator($directory), '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
        foreach ($iterator as [$path]) {
            $realPath = str_replace(DIRECTORY_SEPARATOR, '/', realpath($path));
            $recipePath = str_replace($this->root . '/', '', $realPath);
            $recipeName = preg_replace('/\.php$/i', '', basename($recipePath));
            $recipe = new DocRecipe($recipeName, $recipePath);
            $recipe->parse(file_get_contents($path));
            $this->recipes[$recipePath] = $recipe;
        }
    }

    public function gen(string $destination):? string
    {
        foreach ($this->recipes as $recipe) {
            // $find will try to return DocConfig for a given config $name.
            $findConfig = function (string $name) use ($recipe): ?DocConfig {
                if (array_key_exists($name, $recipe->config)) {
                    return $recipe->config[$name];
                }
                foreach ($recipe->require as $r) {
                    if (array_key_exists($r, $this->recipes)) {
                        if (array_key_exists($name, $this->recipes[$r]->config)) {
                            return $this->recipes[$r]->config[$name];
                        }
                    }
                }
                foreach ($this->recipes as $r) {
                    if (array_key_exists($name, $r->config)) {
                        return $r->config[$name];
                    }
                }
                return null;
            };
            $findConfigOverride = function (DocRecipe $recipe, string $name) use (&$findConfigOverride): ?DocConfig {
                foreach ($recipe->require as $r) {
                    if (array_key_exists($r, $this->recipes)) {
                        if (array_key_exists($name, $this->recipes[$r]->config)) {
                            return $this->recipes[$r]->config[$name];
                        }
                    }
                }
                foreach ($recipe->require as $r) {
                    if (array_key_exists($r, $this->recipes)) {
                        return $findConfigOverride($this->recipes[$r], $name);
                    }
                }
                return null;
            };
            // Replace all {{name}} with link to correct config declaration.
            $replaceLinks = function (string $comment) use ($findConfig): string {
                return preg_replace_callback('#(\{\{(?<name>[\w_:]+)\}\})#', function ($m) use ($findConfig) {
                    $name = $m['name'];
                    $config = $findConfig($name);
                    if ($config !== null) {
                        $md = php_to_md($config->recipePath);
                        $anchor = anchor($name);
                        return "[$name](/docs/$md#$anchor)";
                    }
                    return "{{" . $name . "}}";
                }, $comment);
            };
            $findTask = function (string $name) use ($recipe): ?DocTask {
                if (array_key_exists($name, $recipe->tasks)) {
                    return $recipe->tasks[$name];
                }
                foreach ($recipe->require as $r) {
                    if (array_key_exists($r, $this->recipes)) {
                        if (array_key_exists($name, $this->recipes[$r]->tasks)) {
                            return $this->recipes[$r]->tasks[$name];
                        }
                    }
                }
                foreach ($this->recipes as $r) {
                    if (array_key_exists($name, $r->tasks)) {
                        return $r->tasks[$name];
                    }
                }
                return null;
            };


            $filePath = "$destination/" . php_to_md($recipe->recipePath);

            $toc = '';
            $config = '';
            $tasks = '';
            if (count($recipe->require) > 0) {
                $toc .= "* Require\n";
                foreach ($recipe->require as $r) {
                    $md = php_to_md($r);
                    $toc .= "  * [`{$r}`](/docs/{$md})\n";
                }
            }
            if (count($recipe->config) > 0) {
                $toc .= "* Config\n";
                $config .= "## Config\n";
                foreach ($recipe->config as $c) {
                    $anchor = anchor($c->name);
                    $toc .= "  * [`{$c->name}`](#{$anchor})\n";
                    $config .= "### {$c->name}\n";
                    $searchTerm = urlencode("\"{$c->name}\" in:file language:php path:" . dirname($c->recipePath) . " filename:" . basename($c->recipePath));
                    $config .= "[Source](https://github.com/deployphp/deployer/search?q={$searchTerm})\n\n";
                    $o = $findConfigOverride($recipe, $c->name);
                    if ($o !== null) {
                        $md = php_to_md($o->recipePath);
                        $anchor = anchor($c->name);
                        $config .= "* Overrides [`{$c->name}`](/docs/$md#$anchor) from `$o->recipePath`\n\n";
                    }
                    $config .= $replaceLinks($c->comment);
                    $config .= "\n\n";
                }
            }
            if (count($recipe->tasks) > 0) {
                $toc .= "* Tasks\n";
                $tasks .= "## Tasks\n";
                foreach ($recipe->tasks as $t) {
                    $anchor = anchor($t->name);
                    $desc = "";
                    if ($t->desc !== "") {
                        $desc = " — {$t->desc}";
                    }
                    $toc .= "  * [`{$t->name}`](#{$anchor}){$desc}\n";
                    $tasks .= "### {$t->name}\n";
                    $searchTerm = urlencode("\"{$t->name}\" in:file language:php path:" . dirname($t->recipePath) . " filename:" . basename($t->recipePath));
                    $tasks .= "[Source](https://github.com/deployphp/deployer/search?q={$searchTerm})\n\n";
                    $tasks .= $replaceLinks($t->comment);
                    if (is_array($t->group)) {
                        $tasks .= "\n\n";
                        $tasks .= "This task is group task which contains next tasks:\n";
                        foreach ($t->group as $taskName) {
                            $t = $findTask($taskName);
                            if ($t !== null) {
                                $md = php_to_md($t->recipePath);
                                $anchor = anchor($t->name);
                                $tasks .= "* [`$taskName`](/docs/$md#$anchor)\n";
                            } else {
                                $tasks .= "* `$taskName`\n";
                            }
                        }
                    }
                    $tasks .= "\n\n";
                }
            }

            $output = <<<MD
<!-- DO NOT EDIT THIS FILE! -->
<!-- Instead edit {$recipe->recipePath} -->
<!-- Then run bin/docgen -->

# {$recipe->recipeName}

[Source](/{$recipe->recipePath})

{$recipe->comment}

{$toc}
{$config}
{$tasks}
MD;

            if (!file_exists(dirname($filePath))) {
                mkdir(dirname($filePath), 0755, true);
            }
            file_put_contents($filePath, $output);
        }
        return null;
    }
}

function trimComment(string $line): string
{
    return preg_replace('#^(/\*\*?\s?|\s\*\s?|//\s?)#', '', $line);
}

function indent(string $text): string
{
    return implode("\n", array_map(function ($line) {
        return "  " . $line;
    }, explode("\n", $text)));
}

function php_to_md(string $file): string
{
    return preg_replace('#\.php$#', '.md', $file);
}

function anchor(string $s): string
{
    return str_replace(':', '', $s);
}
