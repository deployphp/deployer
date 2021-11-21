<?php
/*
# Chatwork Recipe

## Installing
  1. Create chatwork account by any manual in the internet
  2. Take chatwork token (Like: b29a700e2d15bef3f26ae6a5c142d1ea) and set `chatwork_token` parameter
  3. Take chatwork room id from url after clicked on the room, and set `chatwork_room_id` parameter
  4. If you want, you can edit `chatwork_notify_text`, `chatwork_success_text` or `chatwork_failure_text`
  5. Require chatwork recipe in your `deploy.php` file

```php
# https://deployer.org/recipes.html

require 'recipe/chatwork.php';
```

Add hook on deploy:
 
```php
before('deploy', 'chatwork:notify');
```

## Configuration

- `chatwork_token` – chatwork bot token, **required** 
- `chatwork_room_id` — chatwork room to push messages to **required**
- `chatwork_notify_text` – notification message template
  ```
  [info]
    [title](*) Deployment Status: Deploying[/title]
    Repo: {{repository}}
    Branch: {{branch}}
    Server: {{hostname}}
    Release Path: {{release_path}}
    Current Path: {{current_path}}
  [/info]
  ```
- `chatwork_success_text` – success template, default:
  ```
  [info]
    [title](*) Deployment Status: Successfully[/title]
    Repo: {{repository}}
    Branch: {{branch}}
    Server: {{hostname}}
    Release Path: {{release_path}}
    Current Path: {{current_path}}
  [/info]"
  ```
- `chatwork_failure_text` – failure template, default:
  ```
  [info]
    [title](*) Deployment Status: Failed[/title]
    Repo: {{repository}}
    Branch: {{branch}}
    Server: {{hostname}}
    Release Path: {{release_path}}
    Current Path: {{current_path}}
  [/info]"
  ```

## Tasks

- `chatwork:notify` – send message to chatwork
- `chatwork:notify:success` – send success message to chatwork
- `chatwork:notify:failure` – send failure message to chatwork

## Usage

If you want to notify only about beginning of deployment add this line only:

```php
before('deploy', 'chatwork:notify');
```

If you want to notify about successful end of deployment add this too:

```php
after('success', 'chatwork:notify:success');
```
If you want to notify about failed deployment add this too:

```php
after('deploy:failed', 'chatwork:notify:failure');
```
 */
namespace Deployer;
use Deployer\Utility\Httpie;

// Chatwork settings
set('chatwork_token', function () {
    throw new \RuntimeException('Please configure "chatwork_token" parameter.');
});
set('chatwork_room_id', function () {
    throw new \RuntimeException('Please configure "chatwork_room_id" parameter.');
});
set('chatwork_api', function () {
   return 'https://api.chatwork.com/v2/rooms/' . get('chatwork_room_id') . '/messages';
});

// The Messages
set('chatwork_notify_text', "[info]\n[title](*) Deployment Status: Deploying[/title]\nRepo: {{repository}}\nBranch: {{branch}}\nServer: {{hostname}}\nRelease Path: {{release_path}}\nCurrent Path: {{current_path}}\n[/info]");
set('chatwork_success_text', "[info]\n[title](*) Deployment Status: Successfully[/title]\nRepo: {{repository}}\nBranch: {{branch}}\nServer: {{hostname}}\nRelease Path: {{release_path}}\nCurrent Path: {{current_path}}\n[/info]");
set('chatwork_failure_text', "[info]\n[title](*) Deployment Status: Failed[/title]\nRepo: {{repository}}\nBranch: {{branch}}\nServer: {{hostname}}\nRelease Path: {{release_path}}\nCurrent Path: {{current_path}}\n[/info]");

// Helpers
task('chatwork_send_message', function() {
    Httpie::post(get('chatwork_api'))
        ->query(['body' => get('chatwork_message'),])
        ->header("X-ChatWorkToken", get('chatwork_token'))
        ->send();
});

// Tasks
desc('Tests messages');
task('chatwork:test', function () {
    set('chatwork_message', get('chatwork_notify_text'));
    invoke('chatwork_send_message');
    set('chatwork_message', get('chatwork_success_text'));
    invoke('chatwork_send_message');
    set('chatwork_message', get('chatwork_failure_text'));
    invoke('chatwork_send_message');
})
    ->once();

desc('Notifies Chatwork');
task('chatwork:notify', function () {
    if (!get('chatwork_token', false)) {
        return;
    }
    
    if (!get('chatwork_room_id', false)) {
        return;
    }
    set('chatwork_message', get('chatwork_notify_text'));
    invoke('chatwork_send_message');
})
    ->once()
    ->hidden();

desc('Notifies Chatwork about deploy finish');
task('chatwork:notify:success', function () {
    if (!get('chatwork_token', false)) {
        return;
    }
      
    if (!get('chatwork_room_id', false)) {
        return;
    }

    set('chatwork_message', get('chatwork_success_text'));
    invoke('chatwork_send_message');
})
    ->once()
    ->hidden();

desc('Notifies Chatwork about deploy failure');
task('chatwork:notify:failure', function () {
    if (!get('chatwork_token', false)) {
        return;
    }
      
    if (!get('chatwork_room_id', false)) {
        return;
    }

    set('chatwork_message', get('chatwork_failure_text'));
    invoke('chatwork_send_message');
})
    ->once()
    ->hidden();
