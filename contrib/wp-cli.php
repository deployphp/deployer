<?php
/*
## Configuration

- `bin/wp` *(optional)*: set WP-CLI binary, automatically detected otherwise.

## Usage

```php
task('deploy:wp-core-download', function() {
    run('cd {{release_or_current_path}} && {{bin/wp}} core download');
});
```
*/
namespace Deployer;

set('bin/wp', function () {
    if (test('[ -f {{release_or_current_path}}/vendor/wp-cli/wp-cli/php/boot-fs.php ]')) {
        return '{{bin/php}} {{release_or_current_path}}/vendor/wp-cli/wp-cli/php/boot-fs.php';
    }

    if (test('[ -f {{deploy_path}}/.dep/wp-cli.phar ]')) {
        return '{{bin/php}} {{deploy_path}}/.dep/wp-cli.phar';
    }

    if (commandExist('wp')) {
        return '{{bin/php}} ' . which('wp');
    }

    warning("WP-CLI binary wasn't found. Installing latest WP-CLI to \"{{deploy_path}}/.dep/wp-cli.phar\".");
    run('cd {{deploy_path}} && curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar');
    run('mv {{deploy_path}}/wp-cli.phar {{deploy_path}}/.dep/wp-cli.phar');
    return '{{bin/php}} {{deploy_path}}/.dep/wp-cli.phar';
});
