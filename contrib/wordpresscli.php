<?php
/*
## Installing

Add to your _deploy.php_

```php
require 'contrib/wordpresscli.php';
```

## Configuration
- `wpcli_webroot`, Path of the root website folder relative to the site root. Defaults to ''
- `wpcli_domain`, Domain name of wordpress site for use with wp cli
## Usage

Wordpress cli tasks.  Currently only wp

```php
after('deploy:publish', 'wp:cache:flush');
```

 */

namespace Deployer;

use Deployer\Host\Host;
use Deployer\Host\Localhost;

function wpcliSitePath(Host $host): string
{
    $deployPath = ($host instanceof Localhost) ? $host->get('deploy_path') : $host->get('current_path');
    $root = $host->get('wpcli_webroot', '');
    $rootPath = (!empty($root)) ? $deployPath . '/' . $root : $deployPath;
    if (empty($rootPath)) {
        return '';
    }

    $option = sprintf('--path="%s"', $rootPath);
    return $option;
}

function wpcliUrl(Host $host): string
{
    $url = $host->get('wpcli_domain', '');
    if (empty($url)) {
        return '';
    }
    $option = sprintf('--url="%s"', $url);
    return $option;
}

function wpcliCommand(Host $host, string $command): string
{
    $command = sprintf('wp %s %s %s', $command,  wpcliSitePath($host), wpcliUrl($host));
    return $command;
}

task('wp:cache:flush', function () {
    $command = wpcliCommand(currentHost(), 'cache flush');
    run($command);
})->desc('Clear wordpress cache');
