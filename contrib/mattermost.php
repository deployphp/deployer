<?php
/*
## Installing

Create a Mattermost incoming webhook, through the administration panel.

Add hook on deploy:

```
before('deploy', 'mattermost:notify');
```

## Configuration

 - `mattermost_webhook` - incoming mattermost webook **required**
   ```
   set('mattermost_webook', 'https://{your-mattermost-site}/hooks/xxx-generatedkey-xxx');
   ```

 - `mattermost_channel` - overrides the channel the message posts in
   ```
   set('mattermost_channel', 'town-square');
   ```

 - `mattermost_username` - overrides the username the message posts as
   ```
   set('mattermost_username', 'deployer');
   ```

 - `mattermost_icon_url` - overrides the profile picture the message posts with
   ```
   set('mattermost_icon_url', 'https://domain.com/your-icon.png');
   ```

 - `mattermost_text` - notification message
   ```
   set('mattermost_text', '_{{user}}_ deploying `{{branch}}` to **{{target}}**');
   ```

 - `mattermost_success_text` – success template, default:
   ```
   set('mattermost_success_text', 'Deploy to **{{target}}** successful {{mattermost_success_emoji}}');
   ```

 - `mattermost_failure_text` – failure template, default:
   ```
   set('mattermost_failure_text', 'Deploy to **{{target}}** failed {{mattermost_failure_emoji}}');
   ```

 - `mattermost_success_emoji` – emoji added at the end of success text
 - `mattermost_failure_emoji` – emoji added at the end of failure text

 For detailed information about Mattermost hooks see: https://developers.mattermost.com/integrate/incoming-webhooks/

## Usage

If you want to notify only about beginning of deployment add this line only:

```php
before('deploy', 'mattermost:notify');
```

If you want to notify about successful end of deployment add this too:

```php
after('deploy:success', 'mattermost:notify:success');
```

If you want to notify about failed deployment add this too:

```php
after('deploy:failed', 'mattermost:notify:failure');
```

 */
namespace Deployer;

use Deployer\Utility\Httpie;

set('mattermost_webhook', null);
set('mattermost_channel', null);
set('mattermost_username', 'deployer');
set('mattermost_icon_url', null);

set('mattermost_success_emoji', ':white_check_mark:');
set('mattermost_failure_emoji', ':x:');

set('mattermost_text', '_{{user}}_ deploying `{{branch}}` to **{{target}}**');
set('mattermost_success_text', 'Deploy to **{{target}}** successful {{mattermost_success_emoji}}');
set('mattermost_failure_text', 'Deploy to **{{target}}** failed {{mattermost_failure_emoji}}');

desc('Notifies mattermost');
task('mattermost:notify', function() {
    if (null === get('mattermost_webhook')) {
        return;
    }

    $body = [
        'text' => get('mattermost_text'),
        'username' => get('mattermost_username'),
    ];

    if (get('mattermost_channel')) {
        $body['channel'] = get('mattermost_channel');
    }
    if (get('mattermost_icon_url')) {
        $body['icon_url'] = get('mattermost_icon_url');
    }

    Httpie::post(get('mattermost_webhook'))->jsonBody($body)->send();
});

desc('Notifies mattermost about deploy finish');
task('mattermost:notify:success', function() {
    if (null === get('mattermost_webhook')) {
        return;
    }

    $body = [
        'text' => get('mattermost_success_text'),
        'username' => get('mattermost_username'),
    ];

    if (get('mattermost_channel')) {
        $body['channel'] = get('mattermost_channel');
    }
    if (get('mattermost_icon_url')) {
        $body['icon_url'] = get('mattermost_icon_url');
    }

    Httpie::post(get('mattermost_webhook'))->jsonBody($body)->send();
});

desc('Notifies mattermost about deploy failure');
task('mattermost:notify:failure', function() {
    if (null === get('mattermost_webhook')) {
        return;
    }

    $body = [
        'text' => get('mattermost_failure_text'),
        'username' => get('mattermost_username'),
    ];

    if (get('mattermost_channel')) {
        $body['channel'] = get('mattermost_channel');
    }
    if (get('mattermost_icon_url')) {
        $body['icon_url'] = get('mattermost_icon_url');
    }

    Httpie::post(get('mattermost_webhook'))->jsonBody($body)->send();
});
