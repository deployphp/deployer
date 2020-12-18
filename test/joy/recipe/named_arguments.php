<?php

namespace Deployer;

localhost();

task('named_arguments', function () {
    run('echo Hello, %name%!', vars: ['name' => 'world']);
});

task('options', function () {
    run('echo Hello, %name%!', ['vars' => ['name' => 'Anton']]);
});

task('options_with_named_arguments', function () {
    // The `options:` arg has higher priority than named arguments.
    run('echo Hello, %name%!', ['vars' => ['name' => 'override']], vars: ['name' => 'world']);
});

task('run_locally_named_arguments', function () {
    runLocally('echo Hello, %name%!', vars: ['name' => 'world']);
});
