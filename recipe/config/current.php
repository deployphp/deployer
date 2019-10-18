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
    $selectedStage = Deployer::get()->getInput()->getArgument('stage');

    on(Deployer::get()->hosts, function (Host $host) use (&$rows, $selectedStage) {
        if ($host->get('stage') !== $selectedStage) {
            return;
        }

        try {
            $rows[] = [
                $host->getHostname(),
                basename($host->get('current_path')),
                $host->get('current_date')->format('Y-m-d H:i:s O'),
            ];
        } catch (\Throwable $e) {
            $rows[] = [
                $host->getHostname(),
                'unknown',
                'unknown',
            ];
        }
    });

    $table = new Table(output());
    $table
        ->setHeaders(['Host', 'Current', 'Release date'])
        ->setRows($rows);
    $table->render();
})->local();
