<?php
/*

## Configuration

- **webpack_encore/package_manager** *(optional)*: set yarn or npm. We try to find if yarn or npm is available and used.

## Usage

```php
// For Yarn
after('deploy:update_code', 'yarn:install');
// For npm
after('deploy:update_code', 'npm:install');

after('deploy:update_code', 'webpack_encore:build');
```
 */
namespace Deployer;

require_once __DIR__ . '/npm.php';
require_once __DIR__ . '/yarn.php';

set('webpack_encore/package_manager', function () {
    if (test('[ -f {{release_path}}/yarn.lock ]')) {
        return 'yarn';
    }

    return 'npm';
});

set('webpack_encore/env', 'production');

desc('Runs webpack encore build');
task('webpack_encore:build', function () {
    $packageManager = get('webpack_encore/package_manager');

    if (!in_array($packageManager, ['npm', 'yarn'], true)) {
        throw new \Exception(sprintf('Package Manager "%s" is not supported', $packageManager));
    }

    run("cd {{release_path}} && {{bin/$packageManager}} run encore {{webpack_encore/env}}");
});
