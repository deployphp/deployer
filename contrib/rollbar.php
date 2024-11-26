<?php

/*

## Configuration

- `rollbar_token` – access token to rollbar api
- `rollbar_comment` – comment about deploy, default to
  ```php
  set('rollbar_comment', '_{{user}}_ deploying `{{what}}` to *{{where}}*');
  ```
- `rollbar_username` – rollbar user name

## Usage

Since you should only notify Rollbar channel of a successful deployment, the `rollbar:notify` task should be executed right at the end.

```php
after('deploy', 'rollbar:notify');
```

 */

namespace Deployer;

use Deployer\Utility\Httpie;

set('rollbar_comment', '_{{user}}_ deploying `{{what}}` to *{{where}}*');

desc('Notifies Rollbar of deployment');
task('rollbar:notify', function () {
    if (!get('rollbar_token', false)) {
        return;
    }

    $params = [
        'access_token' => get('rollbar_token'),
        'environment' => get('where'),
        'revision' => runLocally('git log -n 1 --format="%h"'),
        'local_username' => get('user'),
        'rollbar_username' => get('rollbar_username'),
        'comment' => get('rollbar_comment'),
    ];

    Httpie::post('https://api.rollbar.com/api/1/deploy/')
        ->formBody($params)
        ->send();
})
    ->once();
