<?php
/*
## Installing

Add to your _deploy.php_

```php
require 'contrib/clearpaths.php';
```

## Configuration
- `clear_server_paths`, Array of host paths to clear
- `clear_server_use_sudo',

## Usage

Clear paths on host outside of release directory (rm -rf).  Must be specified as absolute directories.
## WARNING: THIS CAN BE A DESTRUCTIVE COMMAND, ALWAYS MAKE SURE TO USE ABSOLUTE FILE/FOLDER PATHS

```php
before('deploy:publish', 'deploy:clear_server_paths');
```

 */

namespace Deployer;

// List of paths to remove from host.
set('clear_server_paths', []);

// Use sudo for deploy:clear_server_paths task?
set('clear_server_use_sudo', false);

// https://github.com/deployphp/deployer/blob/master/recipe/deploy/clear_paths.php
task('deploy:clear_server_paths', function () {
    $host = currentHost();
    $paths = $host->get('clear_server_paths');
    if (empty($paths)) {
        return;
    }

    $sudo = $host->get('clear_server_use_sudo', false) ? 'sudo' : '';
    $batch = 100;

    $commands = [];
    foreach ($paths as $path) {
        if (strpos('/', $path) !== 0) {
            throw error(sprintf('Path %s is not absolute', $path));
        }
        if (test("[ -d $path ]")) {
            warning(parse("Path \"$path\" not found."));
            continue;
        }
        $commands[] = "$sudo rm -rf $path";
    }
    $chunks = array_chunk($commands, $batch);
    foreach ($chunks as $chunk) {
        $clearCommand = implode('; ', $chunk);
        run($clearCommand);
    }
})->desc('Remove server files and/or directories based on absolute paths');
