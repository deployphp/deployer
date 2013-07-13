<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Start deployer script.
 *
 * @param bool $includeFunction Include or not helpful functions.
 * @return \Deployer\Tool
 */
function deployer($includeFunction = true)
{
    $tool = new Deployer\Tool();

    if ($includeFunction) {
        Deployer\Tool\Context::push($tool);
        include_once __DIR__ . '/Deployer/functions.php';
    }

    return $tool;
}

