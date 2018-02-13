<?php
/* (c) Anton Medvedev <anton@medv.io>
 * (c) Barry vd. Heuvel <barryvdh@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Host\Host;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableCell;

desc('Show current deployment status');
task('config:status', function () {
    $rows = [];
    $hosts = Deployer::get()->hosts;
    on($hosts, function (Host $host) use (&$rows) {
        try {
            $rev = run('cd {{deploy_path}}/current && git log --pretty=format:\'{"hash": "%h", "subject": "%s", "author": {"name": "%aN", "email": "%aE", "date": "%aD"}}\' -n 1 --date=iso');
            $rev = json_decode($rev, true);
            $rows[] = [
                $host->getHostname(),
                $rev['author']['name'],
                date("Y-m-d H:i", strtotime($rev['author']['date'])),
                $rev['hash'],
            ];
            $rows[] = ['', new TableSeparator(array('colspan' => 3))];
            $rows[] = [
                '', new TableCell($rev['subject'], array('colspan' => 3)),
            ];
            $rows[] = [new TableSeparator(array('colspan' => 4)),];
        } catch (\Throwable $e) {
            $rows[] = [
                $host->getHostname(),
                'unknown',
                'unknown',
            ];
            $rows[] = [new TableSeparator(array('colspan' => 4)),];
        }
    });

    // Remove last table seperator
    array_pop($rows);

    $table = new Table(output());
    $table
        ->setHeaders(['Host', 'Author', 'Date', 'Commit' ])
        ->setRows($rows);
    $table->render();
})->local();
