<?php

namespace Deployer;

localhost('prod');
localhost('beta');

set('greet', '_');

task('echo', function () {
    $alias = currentHost()->getAlias();
    run("echo {{greet}}, $alias!");
});

task('ask', function () {
    $answer = ask('Question: What kind of bear is best?');
    writeln($answer);
});

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

task('test_once', function () {
    writeln('SHOULD BE ONCE');
})->once();

