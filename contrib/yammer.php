<?php
/*

Add hook on deploy:

```php
before('deploy', 'yammer:notify');
```

## Configuration

- `yammer_url` – The URL to the message endpoint, default is https://www.yammer.com/api/v1/messages.json
- `yammer_token` *(required)* – Yammer auth token
- `yammer_group_id` *(required)* - Group ID
- `yammer_title` – the title of application, default `{{application}}`
- `yammer_body` – notification message template, default:
  ```
  <em>{{user}}</em> deploying {{branch}} to <strong>{{target}}</strong>
  ```
- `yammer_success_body` – success template, default:
  ```
  Deploy to <strong>{{target}}</strong> successful
  ```
- `yammer_failure_body` – failure template, default:
  ```
  Deploy to <strong>{{target}}</strong> failed
  ```

## Usage

If you want to notify only about beginning of deployment add this line only:

```php
before('deploy', 'yammer:notify');
```

If you want to notify about successful end of deployment add this too:

```php
after('deploy:success', 'yammer:notify:success');
```

If you want to notify about failed deployment add this too:

```php
after('deploy:failed', 'yammer:notify:failure');
```

 */
namespace Deployer;

use Deployer\Utility\Httpie;

set('yammer_url', 'https://www.yammer.com/api/v1/messages.json');

// Title of project
set('yammer_title', function () {
    return get('application', 'Project');
});

// Deploy message
set('yammer_body', '<em>{{user}}</em> deploying {{branch}} to <strong>{{target}}</strong>');
set('yammer_success_body', 'Deploy to <strong>{{target}}</strong> successful');
set('yammer_failure_body', 'Deploy to <strong>{{target}}</strong> failed');

desc('Notifies Yammer');
task('yammer:notify', function () {
    $params = [
        'is_rich_text' => 'true',
        'message_type' => 'announcement',
        'group_id' => get('yammer_group_id'),
        'title' => get('yammer_title'),
        'body' => get('yammer_body'),
    ];

    Httpie::post(get('yammer_url'))
        ->header('Authorization', 'Bearer ' . get('yammer_token'))
        ->header('Content-type', 'application/json')
        ->jsonBody($params)
        ->send();
})
    ->once()
    ->hidden();

desc('Notifies Yammer about deploy finish');
task('yammer:notify:success', function () {
    $params = [
        'is_rich_text' => 'true',
        'message_type' => 'announcement',
        'group_id' => get('yammer_group_id'),
        'title' => get('yammer_title'),
        'body' => get('yammer_success_body'),
    ];

    Httpie::post(get('yammer_url'))
        ->header('Authorization', 'Bearer ' . get('yammer_token'))
        ->header('Content-type', 'application/json')
        ->jsonBody($params)
        ->send();
})
    ->once()
    ->hidden();

desc('Notifies Yammer about deploy failure');
task('yammer:notify:failure', function () {
    $params = [
        'is_rich_text' => 'true',
        'message_type' => 'announcement',
        'group_id' => get('yammer_group_id'),
        'title' => get('yammer_title'),
        'body' => get('yammer_failure_body'),
    ];

    Httpie::post(get('yammer_url'))
        ->header('Authorization', 'Bearer ' . get('yammer_token'))
        ->header('Content-type', 'application/json')
        ->jsonBody($params)
        ->send();
})
    ->once()
    ->hidden();
