<!-- DO NOT EDIT THIS FILE! -->
<!-- Instead edit contrib/slack.php -->
<!-- Then run bin/docgen -->

# slack

[Source](/contrib/slack.php)


## Installing

<a href="https://slack.com/oauth/authorize?&client_id=113734341365.225973502034&scope=incoming-webhook"><img alt="Add to Slack" height="40" width="139" src="https://platform.slack-edge.com/img/add_to_slack.png" srcset="https://platform.slack-edge.com/img/add_to_slack.png 1x, https://platform.slack-edge.com/img/add_to_slack@2x.png 2x" /></a>

Require slack recipe in your `deploy.php` file:

```php
require 'contrib/slack.php';
```

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



* Config
  * [`slack_channel`](#slack_channel)
  * [`slack_title`](#slack_title)
  * [`slack_text`](#slack_text)
  * [`slack_success_text`](#slack_success_text)
  * [`slack_failure_text`](#slack_failure_text)
  * [`slack_rollback_text`](#slack_rollback_text)
  * [`slack_color`](#slack_color)
  * [`slack_success_color`](#slack_success_color)
  * [`slack_failure_color`](#slack_failure_color)
  * [`slack_rollback_color`](#slack_rollback_color)
* Tasks
  * [`slack:notify`](#slacknotify) — Notifying Slack
  * [`slack:notify:success`](#slacknotifysuccess) — Notifying Slack about deploy finish
  * [`slack:notify:failure`](#slacknotifyfailure) — Notifying Slack about deploy failure
  * [`slack:notify:rollback`](#slacknotifyrollback) — Notifying Slack about rollback

## Config
### slack_channel
[Source](https://github.com/deployphp/deployer/search?q=%22slack_channel%22+in%3Afile+language%3Aphp+path%3Acontrib+filename%3Aslack.php)

Channel to publish to, when false the default channel the webhook will be used

### slack_title
[Source](https://github.com/deployphp/deployer/search?q=%22slack_title%22+in%3Afile+language%3Aphp+path%3Acontrib+filename%3Aslack.php)

Title of project

### slack_text
[Source](https://github.com/deployphp/deployer/search?q=%22slack_text%22+in%3Afile+language%3Aphp+path%3Acontrib+filename%3Aslack.php)

Deploy message

### slack_success_text
[Source](https://github.com/deployphp/deployer/search?q=%22slack_success_text%22+in%3Afile+language%3Aphp+path%3Acontrib+filename%3Aslack.php)



### slack_failure_text
[Source](https://github.com/deployphp/deployer/search?q=%22slack_failure_text%22+in%3Afile+language%3Aphp+path%3Acontrib+filename%3Aslack.php)



### slack_rollback_text
[Source](https://github.com/deployphp/deployer/search?q=%22slack_rollback_text%22+in%3Afile+language%3Aphp+path%3Acontrib+filename%3Aslack.php)



### slack_color
[Source](https://github.com/deployphp/deployer/search?q=%22slack_color%22+in%3Afile+language%3Aphp+path%3Acontrib+filename%3Aslack.php)

Color of attachment

### slack_success_color
[Source](https://github.com/deployphp/deployer/search?q=%22slack_success_color%22+in%3Afile+language%3Aphp+path%3Acontrib+filename%3Aslack.php)



### slack_failure_color
[Source](https://github.com/deployphp/deployer/search?q=%22slack_failure_color%22+in%3Afile+language%3Aphp+path%3Acontrib+filename%3Aslack.php)



### slack_rollback_color
[Source](https://github.com/deployphp/deployer/search?q=%22slack_rollback_color%22+in%3Afile+language%3Aphp+path%3Acontrib+filename%3Aslack.php)




## Tasks
### slack:notify
[Source](https://github.com/deployphp/deployer/search?q=%22slack%3Anotify%22+in%3Afile+language%3Aphp+path%3Acontrib+filename%3Aslack.php)



### slack:notify:success
[Source](https://github.com/deployphp/deployer/search?q=%22slack%3Anotify%3Asuccess%22+in%3Afile+language%3Aphp+path%3Acontrib+filename%3Aslack.php)



### slack:notify:failure
[Source](https://github.com/deployphp/deployer/search?q=%22slack%3Anotify%3Afailure%22+in%3Afile+language%3Aphp+path%3Acontrib+filename%3Aslack.php)



### slack:notify:rollback
[Source](https://github.com/deployphp/deployer/search?q=%22slack%3Anotify%3Arollback%22+in%3Afile+language%3Aphp+path%3Acontrib+filename%3Aslack.php)



