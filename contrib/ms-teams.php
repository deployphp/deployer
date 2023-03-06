<?php
/*
## Installing

Require ms-teams recipe in your `deploy.php` file:

Setup:
1. Open MS Teams
2. Navigate to Teams section
3. Select existing or create new team
4. Select existing or create new channel
5. Hover over channel to get three dots, click, in menu select "Connectors"
6. Search for and configure "Incoming Webhook"
7. Confirm/create and copy your Webhook URL
8. Setup deploy.php
    Add in header:
```php
require 'contrib/ms-teams.php';
set('teams_webhook', 'https://outlook.office.com/webhook/...');
```
Add in content:
```php
before('deploy', 'teams:notify');
after('deploy:success', 'teams:notify:success');
after('deploy:failed', 'teams:notify:failure');
```
9.) Sip your coffee

## Configuration

- `teams_webhook` – teams incoming webhook url, **required**
  ```
  set('teams_webhook', 'https://outlook.office.com/webhook/...');
  ```
- `teams_title` – the title of application, default `{{application}}`
- `teams_text` – notification message template, markdown supported
  ```
  set('teams_text', '_{{user}}_ deploying `{{branch}}` to *{{target}}*');
  ```
- `teams_success_text` – success template, default:
  ```
  set('teams_success_text', 'Deploy to *{{target}}* successful');
  ```
- `teams_failure_text` – failure template, default:
  ```
  set('teams_failure_text', 'Deploy to *{{target}}* failed');
  ```

- `teams_color` – color's attachment
- `teams_success_color` – success color's attachment
- `teams_failure_color` – failure color's attachment

## Usage

If you want to notify only about beginning of deployment add this line only:

```php
before('deploy', 'teams:notify');
```

If you want to notify about successful end of deployment add this too:

```php
after('deploy:success', 'teams:notify:success');
```

If you want to notify about failed deployment add this too:

```php
after('deploy:failed', 'teams:notify:failure');
```
 */
namespace Deployer;

use Deployer\Utility\Httpie;

// Title of project
set('teams_title', function () {
    return get('application', 'Project');
});

// Deploy message
set('teams_text', '_{{user}}_ deploying `{{branch}}` to *{{target}}*');
set('teams_success_text', 'Deploy to *{{target}}* successful');
set('teams_failure_text', 'Deploy to *{{target}}* failed');

// Color of attachment
set('teams_color', '#4d91f7');
set('teams_success_color', '#00c100');
set('teams_failure_color', '#ff0909');

desc('Notifies Teams');
task('teams:notify', function () {
    if (!get('teams_webhook', false)) {
        warning('No MS Teams webhook configured');
        return;
    }

    Httpie::post(get('teams_webhook'))->jsonBody([
        "themeColor" => get('teams_color'),
        'text'       => get('teams_text')
    ])->send();
})
    ->once()
    ->hidden();

desc('Notifies Teams about deploy finish');
task('teams:notify:success', function () {
    if (!get('teams_webhook', false)) {
        warning('No MS Teams webhook configured');
        return;
    }

    Httpie::post(get('teams_webhook'))->jsonBody([
        "themeColor" => get('teams_success_color'),
        'text'       => get('teams_success_text')
    ])->send();
})
    ->once()
    ->hidden();

desc('Notifies Teams about deploy failure');
task('teams:notify:failure', function () {
    if (!get('teams_webhook', false)) {
        warning('No MS Teams webhook configured');
        return;
    }

    Httpie::post(get('teams_webhook'))->jsonBody([
        "themeColor" => get('teams_failure_color'),
        'text'       => get('teams_failure_text')
    ])->send();
})
    ->once()
    ->hidden();
