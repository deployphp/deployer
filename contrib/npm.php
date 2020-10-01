<?php
/*
## Installing

Add to your _deploy.php_

~~~php
require 'contrib/npm.php';
~~~

## Configuration

- `bin/npm` *(optional)*: set npm binary, automatically detected otherwise.

## Usage

~~~php
after('deploy:update_code', 'npm:install');
~~~

or if you want use `npm ci` command
~~~php
after('deploy:update_code', 'npm:ci');
~~~

 */
namespace Deployer;

set('bin/npm', function () {
    return run('which npm');
});

desc('Install npm packages');
task('npm:install', function () {
    if (has('previous_release')) {
        if (test('[ -d {{previous_release}}/node_modules ]')) {
            run('cp -R {{previous_release}}/node_modules {{release_path}}');

            // If package.json is unmodified, then skip running `npm install`
            if (!run('diff {{previous_release}}/package.json {{release_path}}/package.json')) {
                return;
            }
        }
    }
    run("cd {{release_path}} && {{bin/npm}} install");
});


desc('Install npm packages with a clean slate');
task('npm:ci', function () {
    run("cd {{release_path}} && {{bin/npm}} ci");
});
