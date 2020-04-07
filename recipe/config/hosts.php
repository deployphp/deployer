<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Symfony\Component\Console\Helper\Table;

desc('Print all hosts');
task('config:hosts', function () {
    $rows = [];
    $selectedStage = Deployer::get()->getInput()->getArgument('stage');

    foreach (Deployer::get()->hosts as $host) {
        if ($selectedStage && $host->get('stage', false) !== $selectedStage) {
            continue;
        }

        $rows[] = [
            $host->getHostname(),
            $host->getRealHostname(),
            $host->get('stage', ''),
            implode(', ', $host->get('roles', [])),
            $host->get('deploy_path', ''),
        ];
    }

    $table = new Table(output());
    $table
        ->setHeaders(['Host', 'Hostname', 'Stage', 'Roles', 'Deploy path'])
        ->setRows($rows);
    $table->render();
})->once();
