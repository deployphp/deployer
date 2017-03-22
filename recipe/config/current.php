<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Task\Context;
use Symfony\Component\Console\Helper\Table;

desc('Show current paths');
task('config:current', function () {
    $rows = [];

    foreach (Deployer::get()->hostSelector->getHosts(input()->getArgument('hostname')) as $hostname => $host) {
        Context::push(new Context($host, input(), output()));
        $rows[] = [$hostname, basename($host->getConfiguration()->get('current_path'))];
        Context::pop();
    }

    $table = new Table(output());
    $table
        ->setHeaders(['Host', 'Current',])
        ->setRows($rows);
    $table->render();
})->local();
