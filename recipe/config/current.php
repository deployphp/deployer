<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Host\Host;
use Symfony\Component\Console\Helper\Table;

desc('Show current paths');
task('config:current', function () {
    $rows = [];

    on(input()->getArgument('stage'), function (Host $host) use (&$rows) {
        $rows[] = [
            $host->getHostname(),
            basename($host->getConfiguration()->get('current_path')),
        ];
    });

    $table = new Table(output());
    $table
        ->setHeaders(['Host', 'Current',])
        ->setRows($rows);
    $table->render();
})->local();
