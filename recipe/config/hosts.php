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
    $hosts = [];

    foreach (Deployer::get()->hosts as $host) {
        $hosts[] = [
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
        ->setRows($hosts);
    $table->render();
});
