<?php
/*
## Installing

<a href="https://slack.com/oauth/authorize?&client_id=113734341365.225973502034&scope=incoming-webhook"><img alt="Add to Slack" height="40" width="139" src="https://platform.slack-edge.com/img/add_to_slack.png" srcset="https://platform.slack-edge.com/img/add_to_slack.png 1x, https://platform.slack-edge.com/img/add_to_slack@2x.png 2x" /></a>


Add hook on deploy:

```php
before('deploy', 'slack:notify');
```

## Configuration

- `slack_webhook` – slack incoming webhook url, **required**
  ```
  set('slack_webhook', 'https://hooks.slack.com/...');
  ```
- `slack_channel` - channel to send notification to. The default is the channel configured in the webhook
- `slack_title` – the title of application, default `{{application}}`
- `slack_text` – notification message template, markdown supported
  ```
  set('slack_text', '_{{user}}_ deploying `{{branch}}` to *{{target}}*');
  ```
- `slack_success_text` – success template, default:
  ```
  set('slack_success_text', 'Deploy to *{{target}}* successful');
  ```
- `slack_failure_text` – failure template, default:
  ```
  set('slack_failure_text', 'Deploy to *{{target}}* failed');
  ```

- `slack_color` – color's attachment
- `slack_success_color` – success color's attachment
- `slack_failure_color` – failure color's attachment
- `slack_fields` - set attachments fields for pretty output in Slack, default:
  ```
  set('slack_fields', []);
  ```

## Usage

If you want to notify only about beginning of deployment add this line only:

```php
before('deploy', 'slack:notify');
```

If you want to notify about successful end of deployment add this too:

```php
after('deploy:success', 'slack:notify:success');
```

If you want to notify about failed deployment add this too:

```php
after('deploy:failed', 'slack:notify:failure');
```

 */
namespace Deployer;

use Deployer\Utility\Httpie;

// Channel to publish to, when false the default channel the webhook will be used
set('slack_channel', false);

// Title of project
set('slack_title', function () {
    return get('application', 'Project');
});

// Deploy message
set('slack_text', '_{{user}}_ deploying `{{target}}` to *{{hostname}}*');
set('slack_success_text', 'Deploy to *{{target}}* successful');
set('slack_failure_text', 'Deploy to *{{target}}* failed');
set('slack_rollback_text', '_{{user}}_ rolled back changes on *{{target}}*');
set('slack_fields', []);

// Color of attachment
set('slack_color', '#4d91f7');
set('slack_success_color', '#00c100');
set('slack_failure_color', '#ff0909');
set('slack_rollback_color', '#eba211');

function checkSlackAnswer($result)
{
    if ('invalid_token' === $result) {
        warning('Invalid Slack token');
        return false;
    }
    return true;
}

desc('Notifies Slack');
task('slack:notify', function () {
    if (!get('slack_webhook', false)) {
        warning('No Slack webhook configured');
        return;
    }

    $attachment = [
        'title' => get('slack_title'),
        'text' => get('slack_text'),
        'color' => get('slack_color'),
        'mrkdwn_in' => ['text'],
    ];

    $result = Httpie::post(get('slack_webhook'))->jsonBody(['channel' => get('slack_channel'), 'attachments' => [$attachment]])->send();
    checkSlackAnswer($result);
})
    ->once()
    ->hidden();

desc('Notifies Slack about deploy finish');
task('slack:notify:success', function () {
    if (!get('slack_webhook', false)) {
        warning('No Slack webhook configured');
        return;
    }

    $attachment = [
        'title' => get('slack_title'),
        'text' => get('slack_success_text'),
        'color' => get('slack_success_color'),
        'fields' => get('slack_fields'),
        'mrkdwn_in' => ['text'],
    ];

    $result = Httpie::post(get('slack_webhook'))->jsonBody(['channel' => get('slack_channel'), 'attachments' => [$attachment]])->send();
    checkSlackAnswer($result);
})
    ->once()
    ->hidden();

desc('Notifies Slack about deploy failure');
task('slack:notify:failure', function () {
    if (!get('slack_webhook', false)) {
        warning('No Slack webhook configured');
        return;
    }

    $attachment = [
        'title' => get('slack_title'),
        'text' => get('slack_failure_text'),
        'color' => get('slack_failure_color'),
        'mrkdwn_in' => ['text'],
    ];

    $result = Httpie::post(get('slack_webhook'))->jsonBody(['channel' => get('slack_channel'), 'attachments' => [$attachment]])->send();
    checkSlackAnswer($result);
})
    ->once()
    ->hidden();

desc('Notifies Slack about rollback');
task('slack:notify:rollback', function () {
    if (!get('slack_webhook', false)) {
        warning('No Slack webhook configured');
        return;
    }

    $attachment = [
        'title' => get('slack_title'),
        'text' => get('slack_rollback_text'),
        'color' => get('slack_rollback_color'),
        'mrkdwn_in' => ['text'],
    ];

    $result = Httpie::post(get('slack_webhook'))->jsonBody(['channel' => get('slack_channel'), 'attachments' => [$attachment]])->send();
    checkSlackAnswer($result);
})
    ->once()
    ->hidden();
