<?php
/**
 * Check hosts for known bugs.
 *
 * @author Eugene Dzhumak <elforastero@ya.ru>
 */

namespace Deployer;

use Deployer\Exception\RuntimeException;
use Deployer\Host\Localhost;

task('check_for_known_bugs', function () {
    $OSWithKnownBugs = [
        'ubuntu 14.04'
    ];

    $OSRegularExpression = implode($OSWithKnownBugs, '|');
    $checkOSCommand = "test -f /etc/lsb-release && cat /etc/lsb-release | grep -E '$OSRegularExpression' -i && exit 1 || exit 0";

    $hosts = Deployer::get()->hosts;
    $client = Deployer::get()->sshClient;
    $warnings = [];

    foreach ($hosts as $hostName => $host) {
        if ($host instanceof Localhost) {
            continue;
        }

        try {
            $client->run($host, $checkOSCommand);
        } catch (RuntimeException $e) {
            $warnings[$host->getHostname()][] = 'Issue with operating system';
        }
    }

    if (empty($warnings)) {
        return;
    }

    foreach ($warnings as $host => $messages) {
        writeln('<error>Host ' . $host . ' has some potential bugs:</error>');

        foreach ($messages as $message) {
            writeln("<error>\t$message</error>");
        }
    }

    writeln('<error>Read more about known bugs: https://github.com/deployphp/deployer/blob/master/KNOWN_BUGS.md</error>');
});
