<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Tool\Context;
use Deployer\Utils\Local;
use Deployer\Utils\Path;
use Deployer\Remote\Remote;
use Deployer\Remote\RemoteGroup;
use Deployer\Remote\RemoteFactory;
use Deployer\Remote\RemoteInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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
     * @var RemoteFactory
     */
    private $remoteFactory;

    /**
     * @var RemoteInterface
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

    public function __construct(Application $app, InputInterface $input, OutputInterface $output, Local $local, RemoteFactory $remoteFactory)
    {
        $this->app = $app;
        $this->input = $input;
        $this->output = $output;
        $this->local = $local;
        $this->remoteFactory = $remoteFactory;
    }

    public function task($name, $descriptionOrCallback, $callback = null)
    {
        if (null === $callback) {
            $description = false;
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
        if (null === $group) {
            $this->remote = $this->remoteFactory->create($server, $user, $password);
        } else {
            if (null === $this->remote) {
                $this->remote = new RemoteGroup();
            }

            if ($this->remote instanceof RemoteGroup) {
                $this->remote->add($group, $this->remoteFactory->create($server, $user, $password));
            } else {
                throw new \RuntimeException("You are trying to connect to group after connecting without group.");
            }

        }
    }

    public function ignore($ignore = array())
    {
        $this->ignore = $ignore;
    }

    public function upload($local, $remote)
    {
        $this->checkConnected();

        $local = Path::normalize(realpath($local));

        if (is_file($local) && is_readable($local)) {
            $this->writeln("Uploading file <info>$local</info> to <info>$remote</info>");
            $this->remote->uploadFile($local, $remote);
        } else if (is_dir($local)) {
            $this->writeln("Uploading from <info>$local</info> to <info>$remote</info>:");

            $ignore = array_map(function ($pattern) {
                $pattern = preg_quote($pattern, '#');
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
            $progress->start($this->output, $files->count());

            /** @var $file \SplFileInfo */
            foreach ($files as $file) {

                $from = Path::normalize($file->getRealPath());
                $to = str_replace($local, '', $from);
                $to = rtrim($remote, '/') . '/' . ltrim($to, '/');

                $this->remote->uploadFile($from, $to);
                $progress->advance();
            }

            $progress->finish();
        } else {
            throw new \RuntimeException("Uploading path '$local' does not exist.");
        }
    }

    public function cd($directory)
    {
        $this->checkConnected();
        $this->remote->cd($directory);
    }

    public function run($command)
    {
        $this->checkConnected();
        $this->writeln("Running command <info>$command</info>");
        $output = $this->remote->execute($command);
        $this->write($output);
        return $output;
    }

    public function runLocally($command)
    {
        $this->writeln("Running locally command <info>$command</info>");
        $output = $this->local->execute($command);
        $this->write($output);
        return $output;
    }

    private function checkConnected()
    {
        if (null === $this->remote) {
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

    public function group($group, \Closure $action)
    {
        if ($this->remote instanceof RemoteGroup) {
            if ($this->remote->isGroupExist($group)) {

                $this->remote->group($group);
                {
                    call_user_func($action);
                }
                $this->remote->endGroup();

            } else {
                throw new \RuntimeException("Group \"$group\" connection does not defined.");
            }
        } else {
            throw new \RuntimeException("An group connection does not defined.");
        }
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
