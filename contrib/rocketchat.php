<?php
/*
## Installing

Create a RocketChat incoming webhook, through the administration panel.

Add hook on deploy:

```
before('deploy', 'rocketchat:notify');
```

## Configuration

 - `rocketchat_webhook` - incoming rocketchat webook **required**
   ```
   set('rocketchat_webhook', 'https://rocketchat.yourcompany.com/hooks/XXXXX');
   ```

 - `rocketchat_title` - the title of the application, defaults to `{{application}}`
 - `rocketchat_text` - notification message
   ```
   set('rocketchat_text', '_{{user}}_ deploying {{branch}} to {{target}}');
   ```

 - `rocketchat_success_text` – success template, default:
  ```
  set('rocketchat_success_text', 'Deploy to *{{target}}* successful');
  ```
 - `rocketchat_failure_text` – failure template, default:
  ```
  set('rocketchat_failure_text', 'Deploy to *{{target}}* failed');
  ```

 - `rocketchat_color` – color's attachment
 - `rocketchat_success_color` – success color's attachment
 - `rocketchat_failure_color` – failure color's attachment

## Usage

If you want to notify only about beginning of deployment add this line only:

```php
before('deploy', 'rocketchat:notify');
```

If you want to notify about successful end of deployment add this too:

```php
after('deploy:success', 'rocketchat:notify:success');
```

If you want to notify about failed deployment add this too:

```php
after('deploy:failed', 'rocketchat:notify:failure');
```

 */
namespace Deployer;

use Deployer\Utility\Httpie;

set('rockchat_title', function() {
    return get('application', 'Project');
});

set('rocketchat_icon_emoji', ':robot:');
set('rocketchat_icon_url', null);

set('rocketchat_channel', null);
set('rocketchat_room_id', null);
set('rocketchat_username', null);
set('rocketchat_webhook', null);

set('rocketchat_color', '#000000');
set('rocketchat_success_color', '#00c100');
set('rocketchat_failure_color', '#ff0909');

set('rocketchat_text', '_{{user}}_ deploying `{{branch}}` to *{{target}}*');
set('rocketchat_success_text', 'Deploy to *{{target}}* successful');
set('rocketchat_failure_text', 'Deploy to *{{target}}* failed');

desc('Notifies RocketChat');
task('rocketchat:notify', function() {
    if (null === get('rocketchat_webhook')) {
        return;
    }

    $body = [
        'text' => get('rockchat_title'),
        'username' => get('rocketchat_username'),
        'attachments' => [[
            'text' => get('rocketchat_text'),
            'color' => get('rocketchat_color'),
        ]]
    ];

    if (get('rocketchat_channel')) {
        $body['channel'] = get('rocketchat_channel');
    }
    if (get('rocketchat_room_id')) {
        $body['roomId'] = get('rocketchat_room_id');
    }
    if (get('rocketchat_icon_url')) {
        $body['avatar'] = get('rocketchat_icon_url');
    } elseif (get('rocketchat_icon_emoji')) {
        $body['emoji'] = get('rocketchat_icon_emoji');
    }

    Httpie::post(get('rocketchat_webhook'))->jsonBody($body)->send();
});

desc('Notifies RocketChat about deploy finish');
task('rocketchat:notify:success', function() {
    if (null === get('rocketchat_webhook')) {
        return;
    }

    $body = [
        'text' => get('rockchat_title'),
        'username' => get('rocketchat_username'),
        'attachments' => [[
            'text' => get('rocketchat_success_text'),
            'color' => get('rocketchat_success_color'),
        ]]
    ];

    if (get('rocketchat_channel')) {
        $body['channel'] = get('rocketchat_channel');
    }
    if (get('rocketchat_room_id')) {
        $body['roomId'] = get('rocketchat_room_id');
    }
    if (get('rocketchat_icon_url')) {
        $body['avatar'] = get('rocketchat_icon_url');
    } elseif (get('rocketchat_icon_emoji')) {
        $body['emoji'] = get('rocketchat_icon_emoji');
    }

    Httpie::post(get('rocketchat_webhook'))->jsonBody($body)->send();
});

desc('Notifies RocketChat about deploy failure');
task('rocketchat:notify:failure', function() {
    if (null === get('rocketchat_webhook')) {
        return;
    }

    $body = [
        'text' => get('rockchat_title'),
        'username' => get('rocketchat_username'),
        'attachments' => [[
            'color' => get('rocketchat_failure_color'),
            'text' => get('rocketchat_failure_text')
        ]]
    ];

    if (get('rocketchat_channel')) {
        $body['channel'] = get('rocketchat_channel');
    }
    if (get('rocketchat_room_id')) {
        $body['roomId'] = get('rocketchat_room_id');
    }
    if (get('rocketchat_icon_url')) {
        $body['avatar'] = get('rocketchat_icon_url');
    } elseif (get('rocketchat_icon_emoji')) {
        $body['emoji'] = get('rocketchat_icon_emoji');
    }

    Httpie::post(get('rocketchat_webhook'))->jsonBody($body)->send();
});

