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
    $hosts = Deployer::get()->hosts;

    on($hosts, function (Host $host) use (&$rows) {
        try {
            $rows[] = [
                $host->getHostname(),
                basename($host->getConfig()->get('current_path')),
            ];
        } catch (\Throwable $e) {
            $rows[] = [
                $host->getHostname(),
                'unknown',
            ];
        }
    });

    $table = new Table(output());
    $table
        ->setHeaders(['Host', 'Current',])
        ->setRows($rows);
    $table->render();
})->local();
