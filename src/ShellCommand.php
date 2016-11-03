<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Console\InitCommand;
use Deployer\Console\WorkerCommand;
use Deployer\Console\Application;
use Deployer\Server;
use Deployer\Stage\StageStrategy;
use Deployer\Task;
use Deployer\Collection;
use Deployer\Console\TaskCommand;
use Symfony\Component\Console;

class ShellCommand
{
    private $command;

    public function __construct($command)
    {
        $this->command = $command;
    }

    public function getForRunning()
    {
        $command = str_replace("<secret>", "", $this->command);
        $command = str_replace("</secret>", "", $command);
        return $command;
    }

    public function __toString()
    {
        return $this->getForRunning();
    }

    public function getForPrinting()
    {
        return preg_replace('|<secret>.*</secret>|s', "[SECRET HIDDEN]", $this->command);
    }
}
