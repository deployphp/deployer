<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Deployer\Tool;
use Deployer\Tool\Context;
use Deployer\Utils\Local;
use Deployer\Remote\RemoteFactory;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Start deployer script.
 *
 * @param bool $includeFunction Include or not helpful functions.
 * @param array|null $argv
 * @return \Deployer\Tool
 */
function deployer($includeFunction = true, array $argv = null)
{
    $tool = new Tool(
        new Application('Deployer', '0.4.2'),
        new ArgvInput($argv),
        new ConsoleOutput(),
        new Local(),
        new RemoteFactory()
    );

    if ($includeFunction) {
        Context::push($tool);
        include_once __DIR__ . '/Deployer/functions.php';
    }

    return $tool;
}

