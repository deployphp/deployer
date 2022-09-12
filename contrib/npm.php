<?php
/*
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

// Uses `npm ci` command. This command is similar to npm install,
// except it's meant to be used in automated environments such as
// test platforms, continuous integration, and deployment -- or
// any situation where you want to make sure you're doing a clean
// install of your dependencies.
desc('Installs npm packages');
task('npm:install', function () {
    run("cd {{release_path}} && {{bin/npm}} ci");
});
