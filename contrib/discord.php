<?php
/*
## Installing

Add hook on deploy:

```php
before('deploy', 'discord:notify');
```

## Configuration

- `discord_channel` – Discord channel ID, **required**
- `discord_token` – Discord channel token, **required**

- `discord_notify_text` – notification message template, markdown supported, default:
  ```markdown
  :information_source: **{{user}}** is deploying branch `{{branch}}` to _{{target}}_
  ```
- `discord_success_text` – success template, default:
  ```markdown
  :white_check_mark: Branch `{{branch}}` deployed to _{{target}}_ successfully
  ```
- `discord_failure_text` – failure template, default:
  ```markdown
  :no_entry_sign: Branch `{{branch}}` has failed to deploy to _{{target}}_

## Usage

If you want to notify only about beginning of deployment add this line only:

```php
before('deploy', 'discord:notify');
```

If you want to notify about successful end of deployment add this too:

```php
after('deploy:success', 'discord:notify:success');
```

If you want to notify about failed deployment add this too:

```php
after('deploy:failed', 'discord:notify:failure');
```
 */
namespace Deployer;

use Deployer\Task\Context;
use Deployer\Utility\Httpie;

set('discord_webhook', function () {
    return 'https://discordapp.com/api/webhooks/{{discord_channel}}/{{discord_token}}/slack';
});

// Deploy messages
set('discord_notify_text', function() {
    return [
        'text' => parse(':information_source: **{{user}}** is deploying branch `{{branch}}` to _{{target}}_'),
    ];
});
set('discord_success_text', function() {
    return [
        'text' => parse(':white_check_mark: Branch `{{branch}}` deployed to _{{target}}_ successfully'),
    ];
});
set('discord_failure_text', function() {
    return [
        'text' => parse(':no_entry_sign: Branch `{{branch}}` has failed to deploy to _{{target}}_'),
    ];
});

// The message
set('discord_message', 'discord_notify_text');

// Helpers
task('discord_send_message', function(){
    $message = get(get('discord_message'));

    Httpie::post(get('discord_webhook'))->jsonBody($message)->send();
});

// Tasks
desc('Tests messages');
task('discord:test', function () {
    set('discord_message', 'discord_notify_text');
    invoke('discord_send_message');
    set('discord_message', 'discord_success_text');
    invoke('discord_send_message');
    set('discord_message', 'discord_failure_text');
    invoke('discord_send_message');
})
    ->once();

desc('Notifies Discord');
task('discord:notify', function () {
    set('discord_message', 'discord_notify_text');
    invoke('discord_send_message');
})
    ->once()
    ->isHidden();

desc('Notifies Discord about deploy finish');
task('discord:notify:success', function () {
    set('discord_message', 'discord_success_text');
    invoke('discord_send_message');
})
    ->once()
    ->isHidden();

desc('Notifies Discord about deploy failure');
task('discord:notify:failure', function () {
    set('discord_message', 'discord_failure_text');
    invoke('discord_send_message');
})
    ->once()
    ->isHidden();
