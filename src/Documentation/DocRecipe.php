<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Documentation;

class DocRecipe
{
    /**
     * @var string
     */
    public $recipeName;
    /**
     * @var string
     */
    public $recipePath;
    /**
     * @var string
     */
    public $comment;
    /**
     * @var string[]
     */
    public $require = [];
    /**
     * @var DocConfig[]
     */
    public $config = [];
    /**
     * @var DocTask[]
     */
    public $tasks = [];

    public function __construct(string $recipeName, string $recipePath)
    {
        $this->recipeName = $recipeName;
        $this->recipePath = $recipePath;
    }

    /**
     * @return bool|int
     */
    public function parse(string $content)
    {
        $comment = '';
        $desc = '';
        $currentTask = null;

        $content = str_replace("\r\n", "\n", $content);

        $state = 'root';
        $lines = explode("\n", $content);

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            $m = [];
            $match = function ($regexp) use ($line, &$m) {
                return preg_match("#$regexp#", $line, $m);
            };
            switch ($state) {
                case 'root':
                    if ($match('^/\*\*?')) {
                        $state = 'comment';
                        $comment .= trim_comment($line) . "\n";
                        break;
                    }
                    if ($match('^//')) {
                        $comment .= trim_comment($line) . "\n";
                        break;
                    }
                    if ($match('^require.+?[\'"](?<recipe>.+?)[\'"]')) {
                        $this->require[] = dirname($this->recipePath) . $m['recipe'];
                        break;
                    }
                    if ($match('^set\([\'"](?<config_name>[\w_:\-/]+?)[\'"]')) {
                        $set = new DocConfig();
                        $set->name = $m['config_name'];
                        $set->comment = trim($comment);
                        $comment = '';
                        $set->recipePath = $this->recipePath;
                        $set->lineNumber = $i + 1;
                        if (preg_match('#^set\(.+?,\s(?<value>.+?)\);$#', $line, $m)) {
                            $set->defaultValue = $m['value'];
                        }
                        if (preg_match('#^set\(.+?,\s\[$#', $line, $m)) {
                            $multiLineArray = "[\n";
                            $line = $lines[++$i];
                            while (!preg_match('/^]/', $line)) {
                                $multiLineArray .= $line . "\n";
                                $line = $lines[++$i];
                            }
                            $multiLineArray .= "]";
                            $set->defaultValue = $multiLineArray;
                        }
                        $this->config[$set->name] = $set;
                        break;
                    }
                    if ($match('^desc\([\'"](?<desc>.+?)[\'"]\);$')) {
                        $desc = $m['desc'];
                        break;
                    }
                    if ($match('^task\([\'"](?<task_name>[\w_:-]+?)[\'"],\s\[$')) {
                        $task = new DocTask();
                        $task->name = $m['task_name'];
                        $task->desc = $desc;
                        $task->comment = trim($comment);
                        $comment = '';
                        $task->group = [];
                        $task->recipePath = $this->recipePath;
                        $task->lineNumber = $i + 1;
                        $this->tasks[$task->name] = $task;
                        $state = 'group_task';
                        $currentTask = $task;
                        break;
                    }
                    if ($match('^task\([\'"](?<task_name>[\w_:-]+?)[\'"]')) {
                        $task = new DocTask();
                        $task->name = $m['task_name'];
                        $task->desc = $desc;
                        $task->comment = trim($comment);
                        $comment = '';
                        $task->recipePath = $this->recipePath;
                        $task->lineNumber = $i + 1;
                        $this->tasks[$task->name] = $task;
                        break;
                    }
                    if ($match('^<\?php')) {
                        break;
                    }
                    if ($match('^namespace Deployer;$')) {
                        $this->comment = $comment;
                        break;
                    }

                    $desc = '';
                    $comment = '';
                    break;

                case  'comment':
                    if ($match('\*/\s*$')) {
                        $state = 'root';
                        break;
                    }
                    $comment .= trim_comment($line) . "\n";
                    break;

                case 'group_task':
                    if ($match('^\s+\'(?<task_name>[\w_:-]+?)\',$')) {
                        $currentTask->group[] = $m['task_name'];
                        break;
                    }
                    $state = 'root';
                    break;
            }
        }
        return false;
    }
}
