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

    public function __construct()
    {
        $this->app = new Application('Deployer', '0.3.0');
        $this->input = new ArgvInput();
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

    public function connect($server, $user, $password, $name = null)
    {
        $this->writeln(sprintf("Connecting to <info>%s%s</info>", $server, $name ? " ($name)" : ""));
        $this->remote[] = array(
            'name'       => $name,
            'connection' => new Remote($server, $user, $password),
        );
    }

    public function ignore($ignore = array())
    {
        $this->ignore = $ignore;
    }

    public function upload($local, $remote, $serverName = null)
    {
        $this->checkConnected();

        $local = realpath($local);

        if (is_file($local) && is_readable($local)) {
            if ($serverName) {
                $this->writeln("Uploading file <info>$local</info> to <info>$remote</info> (<info>$serverName</info>)");
                $this->getRemoteByName($serverName)->uploadFile($local, $remote);
            }
            else {
                $this->writeln("Uploading file <info>$local</info> to <info>$remote</info>");
                foreach($this->remote as $item) {
                    $item['connection']->uploadFile($local, $remote);
                }
            }
        } else if (is_dir($local)) {
            if ($serverName)
                $this->writeln("Uploading from <info>$local</info> to <info>$remote</info> (<info>$serverName</info>):");
            else
                $this->writeln("Uploading from <info>$local</info> to <info>$remote</info>:");

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

            if ($serverName) {
                $progress->start($this->output, $files->count());
                $this->uploadFiles($files, $local, $remote, $this->getRemoteByName($serverName), $progress);
            }
            else {
                $progress->start($this->output, $files->count() * sizeof($this->remote));
                foreach($this->remote as $item) {
                    $this->uploadFiles($files, $local, $remote, $item['connection'], $progress);
                }
            }

            $progress->finish();
        }
        else {
            throw new \RuntimeException("Uploading path '$local' does not exist.");
        }
    }

    private function uploadFiles($files, $local, $remote, $server, &$progress)
    {
        foreach ($files as $file) {
            $from = $file->getRealPath();
            $to = str_replace($local, '', $from);
            $to = rtrim($remote, '/') . '/' . ltrim($to, '/');

            $server->uploadFile($from, $to);
            $progress->advance();
        }
    }

    public function cd($directory, $serverName = null)
    {
        $this->checkConnected($serverName);

        if ($serverName) {
            $this->getRemoteByName($serverName)->cd($directory);
        }
        else {
            foreach($this->remote as $item) {
                $item['connection']->cd($directory);
            }
        }
    }

    public function run($command, $serverName = null)
    {
        $this->checkConnected($serverName);
        $this->writeln("Running command <info>$command</info>" . ($serverName ? " (<info>$serverName</info>)" : ""));

        if ($serverName) {
            $output = $this->getRemoteByName($serverName)->execute($command);
            $this->write($output);
        }
        else {
            foreach($this->remote as $item) {
                $output = $item['connection']->execute($command);
                $this->write($output);
            }
        }
    }

    public function runLocally($command)
    {
        $this->writeln("Running locally command <info>$command</info>");
        $output = $this->local->execute($command);
        $this->write($output);
    }

    private function getRemoteByName($serverName = null)
    {
        foreach($this->remote as $item) {
            if ($item['name'] === $serverName) {
                return $item['connection'];
            }
        }

        return null;
    }

    private function checkConnected($serverName = null)
    {
        if ($serverName) {
            if (null === $this->getRemoteByName($serverName)) {
                throw new \RuntimeException("You need connect to server first.");
            }
        }
        else {
            if (!sizeof($this->remote)) {
                throw new \RuntimeException("You need connect to server first.");
            }
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