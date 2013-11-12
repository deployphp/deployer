<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Tool\Context;
use Deployer\Tool\Local;
use Deployer\Tool\Remote;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressHelper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Finder\Finder;

class Tool
{
    /**
     * @var Task[]
     */
    private $tasks = array();

    /**
     * @var Application
     */
    private $app;

    /**
     * @var \Symfony\Component\Console\Input\ArgvInput
     */
    private $input;

    /**
     * @var \Symfony\Component\Console\Output\ConsoleOutput
     */
    private $output;

    /**
     * @var Remote[]
     */
    private $remote;

    /**
     * @var Local
     */
    private $local;

    /**
     * @var array
     */
    private $ignore = array();

    public function __construct(array $argv = null)
    {
        $this->app = new Application('Deployer', '0.3.0');
        $this->input = new ArgvInput($argv);
        $this->output = new ConsoleOutput();
        $this->local = new Local();
    }

    public function task($name, $descriptionOrCallback, $callback = null)
    {
        if (null === $callback) {
            $description = '';
            $callback = $descriptionOrCallback;
        } else {
            $description = $descriptionOrCallback;
        }

        if (is_array($callback)) {
            $that = $this;
            $task = new Task($name, $description, function () use ($that, $callback) {
                $tasks = $that->getTasks();
                foreach ($callback as $name) {
                    if (isset($tasks[$name])) {
                        $tasks[$name]->run();
                    } else {
                        throw new \InvalidArgumentException("Task '$name' does not exist.");
                    }
                }
            });
        } else {
            $task = new Task($name, $description, $callback);
        }

        $this->tasks[$name] = $task;
    }

    public function start()
    {
        $this->app->addCommands($this->getCommands());
        $this->app->run($this->input, $this->output);
    }

    public function connect($server, $user, $password, $group = null)
    {
        $this->writeln(sprintf("Connecting to <info>%s%s</info>", $server, $group ? " ($group)" : ""));
        $this->remote[] = array(
            'group'      => $group,
            'connection' => new Remote($server, $user, $password),
        );
    }

    public function ignore($ignore = array())
    {
        $this->ignore = $ignore;
    }

    public function upload($local, $remote, $group = null)
    {
        $this->checkConnected($group);

        $local = realpath($local);
        $connections = $group ? $this->getGroupServers($group) : $this->remote;

        if (is_file($local) && is_readable($local)) {
            $this->writeln("Uploading file <info>$local</info> to <info>$remote</info>");
            foreach($connections as $item) {
                $item['connection']->uploadFile($local, $remote);
            }
        } else if (is_dir($local)) {
            $this->writeln("Uploading from <info>$local</info> to <info>$remote</info>" . ($group ? " (<info>$group</info>):" : ":"));

            $ignore = array_map(function ($pattern) {
                $pattern = preg_quote($pattern);
                $pattern = str_replace('\*', '(.*?)', $pattern);
                $pattern = "#$pattern#";
                return $pattern;
            }, $this->ignore);

            $finder = new Finder();
            $files = $finder
                ->files()
                ->ignoreUnreadableDirs()
                ->ignoreVCS(true)
                ->filter(function (\SplFileInfo $file) use ($ignore) {
                    foreach ($ignore as $pattern) {
                        if (preg_match($pattern, $file->getRealPath())) {
                            return false;
                        }
                    }
                    return true;
                })
                ->in($local);

            /** @var $progress ProgressHelper */
            $progress = $this->app->getHelperSet()->get('progress');
            $progress->start($this->output, $files->count() * sizeof($connections));

            foreach($connections as $item) {
                foreach ($files as $file) {
                    $from = $file->getRealPath();
                    $to = str_replace($local, '', $from);
                    $to = rtrim($remote, '/') . '/' . ltrim($to, '/');

                    $item['connection']->uploadFile($from, $to);
                    $progress->advance();
                }
            }

            $progress->finish();
        }
        else {
            throw new \RuntimeException("Uploading path '$local' does not exist.");
        }
    }

    public function cd($directory, $group = null)
    {
        $this->checkConnected($group);

        $connections = $group ? $this->getGroupServers($group) : $this->remote;
        foreach($connections as $item) {
            $item['connection']->cd($directory);
        }
    }

    public function run($command, $group = null)
    {
        $this->checkConnected($group);
        $this->writeln("Running command <info>$command</info>" . ($group ? " (<info>$group</info>)" : ""));

        $connections = $group ? $this->getGroupServers($group) : $this->remote;
        foreach($connections as $item) {
            $output = $item['connection']->execute($command);
            $this->write($output);
        }
    }

    public function runLocally($command)
    {
        $this->writeln("Running locally command <info>$command</info>");
        $output = $this->local->execute($command);
        $this->write($output);
    }

    private function getGroupServers($group = null)
    {
        $result = [];
        foreach($this->remote as $item) {
            if ($item['group'] === $group) {
                $result[] = $item;
            }
        }

        return $result;
    }

    private function checkConnected($group = null)
    {
        $connections = $group ? $this->getGroupServers($group) : $this->remote;
        if (!sizeof($connections)) {
            throw new \RuntimeException("You need connect to server first.");
        }
    }

    public function write($message)
    {
        $this->output->write($message);
    }

    public function writeln($message)
    {
        $this->output->writeln($message);
    }

    /**
     * @return Application
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * @return Task[]
     */
    public function getTasks()
    {
        return $this->tasks;
    }

    /**
     * @return Command[]
     */
    public function getCommands()
    {
        $commands = array();

        foreach ($this->tasks as $task) {
            if (!$task->isPrivate()) {
                $commands[] = $task->createCommand();
            }
        }

        return $commands;
    }
}