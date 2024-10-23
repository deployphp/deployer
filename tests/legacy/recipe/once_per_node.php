<?php

namespace Deployer;

localhost('group_a_1')
    ->setHostname('localhost');
localhost('group_a_2')
    ->setHostname('localhost');
localhost('group_b_1')
    ->setLabels(['node' => 'anna']);
localhost('group_b_2')
    ->setLabels(['node' => 'anna']);

task('test_once_per_node', function () {
    writeln('alias: {{alias}} hostname: {{hostname}}');
})->oncePerNode();
