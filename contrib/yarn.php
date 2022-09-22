<?php
/*
## Configuration

- **bin/yarn** *(optional)*: set Yarn binary, automatically detected otherwise.

## Usage

```php
after('deploy:update_code', 'yarn:install');
```
 */
namespace Deployer;

set('bin/yarn', function () {
    return which('yarn');
});

// In there is a {{previous_release}}, node_modules will be copied from it before installing deps with yarn.
desc('Installs Yarn packages');
task('yarn:install', function () {
    if (has('previous_release')) {
        if (test('[ -d {{previous_release}}/node_modules ]')) {
            run('cp -R {{previous_release}}/node_modules {{release_path}}');
        }
    }
    run("cd {{release_path}} && {{bin/yarn}}");
});
