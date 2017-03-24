<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Task\Context;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;

desc('Print host configuration');
task('config:dump', function () {
    $host = Context::get()->getHost();
    $common = Deployer::get()->config;
    $config = Context::get()->getConfig();
    $dump = [];

    foreach ($common as $name => $value) {
        try {
            $config->get($name);
        } catch (\RuntimeException $exception) {
            // Ignore fails.
            $message = 'Failed to dump';
            $config->set($name, output()->isDecorated() ? "\033[1;30m$message\033[0m" : $message);
        }
    }

    foreach ($config->getCollection() as $name => $value) {
        if (is_array($value)) {
            $value = json_encode($value, JSON_PRETTY_PRINT);
        } elseif (is_bool($value)) {
            $value = $value ? 'Yes' : 'No';
        }

        $dump[] = [$name, $value];
    }

    $io = new SymfonyStyle(input(), output());
    $io->section("[{$host->getHostname()}]");

    $table = new Table(output());
    $table
        ->setHeaders(['Parameter', 'Value',])
        ->setRows($dump);
    $table->render();
});
