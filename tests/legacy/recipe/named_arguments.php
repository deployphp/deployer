<?php // lint >= 8.0

namespace Deployer;

localhost();

task('named_arguments', function () {
    run('echo "Hello, $name!"', env: ['name' => 'world']);
});

task('options', function () {
    run('echo "Hello, $name!"', ['env' => ['name' => 'Anton']]);
});

task('options_with_named_arguments', function () {
    // The `options:` arg has higher priority than named arguments.
    run('echo "Hello, $name!"', ['env' => ['name' => 'override']], env: ['name' => 'world']);
});

task('run_locally_named_arguments', function () {
    runLocally('echo "Hello, $name!"', env: ['name' => 'world']);
});
