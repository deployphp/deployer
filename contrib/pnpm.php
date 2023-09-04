<?php
/*
## Configuration

- **bin/pnpm** *(optional)*: set pnpm binary, automatically detected otherwise.

## Usage

```php
after('deploy:update_code', 'pnpm:install');
```
 */

namespace Deployer;

set('bin/pnpm', function () {
    return which('pnpm');
});

// In there is a {{previous_release}}, node_modules will be copied from it before installing deps with pnpm.
desc('Installs pnpm packages');
task('pnpm:install', function () {
    if (has('previous_release')) {
        if (test('[ -d {{previous_release}}/node_modules ]')) {
            run('cp -R {{previous_release}}/node_modules {{release_path}}');
        }
    }
    run("cd {{release_path}} && {{bin/pnpm}} i --frozen-lockfile");
});
