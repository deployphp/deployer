<?php
/*
## Installing

Require ntfy.sh recipe in your `deploy.php` file:

Setup:
1. Setup deploy.php
    Add in header:
```php
require 'contrib/ntfy.php';
set('ntfy_topic', 'ntfy.sh/mytopic');
```
Add in content:
```php
before('deploy', 'ntfy:notify');
after('deploy:success', 'ntfy:notify:success');
after('deploy:failed', 'ntfy:notify:failure');
```
9.) Sip your coffee

## Configuration

- `ntfy_server` – ntfy server url, default `ntfy.sh`
  ```
  set('ntfy_server', 'ntfy.sh');
  ```
- `ntfy_topic` – ntfy topic, **required**
  ```
  set('ntfy_topic', 'mysecrettopic');
  ```
- `ntfy_title` – the title of the message, default `{{application}}`
- `ntfy_text` – notification message template
  ```
  set('ntfy_text', '_{{user}}_ deploying `{{branch}}` to *{{target}}*');
  ```
- `ntfy_tags` – notification message tags / emojis (comma separated)
  ```
  set('ntfy_tags', `information_source`);
  ```
- `ntfy_priority` – notification message priority (integer)
  ```
  set('ntfy_priority', 5);
  ```
- `ntfy_success_text` – success template, default:
  ```
  set('ntfy_success_text', 'Deploy to *{{target}}* successful');
  ```
- `ntfy_success_tags` – success tags / emojis (comma separated)
  ```
  set('ntfy_success_tags', `white_check_mark,champagne`);
  ```
- `ntfy_success_priority` – success notification message priority
- `ntfy_failure_text` – failure template, default:
  ```
  set('ntfy_failure_text', 'Deploy to *{{target}}* failed');
  ```
- `ntfy_failure_tags` – failure tags / emojis (comma separated)
  ```
  set('ntfy_failure_tags', `warning,skull`);
  ```
- `ntfy_failure_priority` – failure notification message priority


## Usage

If you want to notify only about beginning of deployment add this line only:

```php
before('deploy', 'ntfy:notify');
```

If you want to notify about successful end of deployment add this too:

```php
after('deploy:success', 'ntfy:notify:success');
```

If you want to notify about failed deployment add this too:

```php
after('deploy:failed', 'ntfy:notify:failure');
```
 */
namespace Deployer;

use Deployer\Utility\Httpie;

set('ntfy_server', 'ntfy.sh');

// Title of project
set('ntfy_title', function () {
    return get('application', 'Project');
});

// Deploy message
set('ntfy_text', '_{{user}}_ deploying `{{branch}}` to *{{target}}*');
set('ntfy_success_text', 'Deploy to *{{target}}* successful');
set('ntfy_failure_text', 'Deploy to *{{target}}* failed');

// Message tags
set('ntfy_tags', '');
set('ntfy_success_tags', '');
set('ntfy_failure_tags', '');

desc('Notifies ntfy server');
task('ntfy:notify', function () {
    if (!get('ntfy_topic', false)) {
        warning('No ntfy topic configured');
        return;
    }

    Httpie::post(get('ntfy_server'))->jsonBody([
        "topic"     => get('ntfy_topic'),
        "title"   => get('ntfy_title'),
        "message"   => get('ntfy_text'),
        "tags"   => explode(",", get('ntfy_tags')),
        "priority"   => get('ntfy_priority'),
    ])->send();
})
    ->once()
    ->hidden();

desc('Notifies ntfy server about deploy finish');
task('ntfy:notify:success', function () {
    if (!get('ntfy_topic', false)) {
        warning('No ntfy topic configured');
        return;
    }

    Httpie::post(get('ntfy_server'))->jsonBody([
        "topic"     => get('ntfy_topic'),
        "title"   => get('ntfy_title'),
        "message"   => get('ntfy_success_text'),
        "tags"   => explode(",", get('ntfy_success_tags')),
        "priority"   => get('ntfy_success_priority'),
    ])->send();
})
    ->once()
    ->hidden();

desc('Notifies ntfy server about deploy failure');
task('ntfy:notify:failure', function () {
    if (!get('ntfy_topic', false)) {
        warning('No ntfy topic configured');
        return;
    }

    Httpie::post(get('ntfy_server'))->jsonBody([
        "topic"     => get('ntfy_topic'),
        "title"   => get('ntfy_title'),
        "message"   => get('ntfy_failure_text'),
        "tags"   => explode(",", get('ntfy_failure_tags')),
        "priority"   => get('ntfy_failure_priority'),
    ])->send();
})
    ->once()
    ->hidden();
