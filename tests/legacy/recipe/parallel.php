<?php

namespace Deployer;

localhost('prod');
localhost('beta')
    ->set('host_level_callback_config', function () {
        return 'from callback';
    });

// testServer:

task('ask', function () {
    $answer = ask('Question: What kind of bear is best?');
    writeln($answer);
});

// testWorker, testOption:

set('greet', '_');

task('echo', function () {
    $alias = currentHost()->getAlias();
    run("echo {{greet}}, $alias!");
});

// testCachedHostConfig:

set('upper_host', function () {
    writeln('running ' . (Deployer::isWorker() ? 'worker' : 'master') . ' on ' . currentHost()->getAlias());
    return strtoupper(currentHost()->getAlias());
});

task('cache_config_test', function () {
    writeln('echo 1: {{upper_host}}');
});

after('cache_config_test', function () {
    writeln('echo 2: {{upper_host}}');
});

// testHostConfigFromCallback:

set('host_level_callback_config', 'from global');

task('host_config_from_callback', function () {
   writeln('config value is {{host_level_callback_config}}');
});

