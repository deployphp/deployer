<?php
/*
## Configuration

- **bin/bun** *(optional)*: set bun binary, automatically detected otherwise.

## Usage

```php
after('deploy:vendors', function () {
    invoke('bun:install');
    invoke('bun:build');
});
```
 */

namespace Deployer;

set('bin/bun', function () {
    return which('bun');
});

desc('Installs dependencies using bun');
task('bun:install', function () {
    run("cd {{release_path}} && {{bin/bun}} i");
});

desc('Runs the build command using bun');
task('bun:build', fn () => run('cd {{release_path}} && {{bin/bun}} run build'));
