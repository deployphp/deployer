<?php
/*
## Installing

Add to your _deploy.php_

```php
require 'contrib/npm.php';
```

## Configuration

- `bin/npm` *(optional)*: set npm binary, automatically detected otherwise.

## Usage

```php
after('deploy:update_code', 'npm:install');
```

 */
namespace Deployer;

set('bin/npm', function () {
    return which('npm');
});

// In there is a {{previous_release}}, node_modules will be copied from it before installing deps.
desc('Installs npm packages');
task('npm:install', function () {
    if (has('previous_release')) {
        if (test('[ -d {{previous_release}}/node_modules ]')) {
            run('cp -R {{previous_release}}/node_modules {{release_path}}');
        }
    }
    run("cd {{release_path}} && {{bin/npm}} install");
});
