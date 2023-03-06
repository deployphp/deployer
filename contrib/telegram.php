<?php
/*
## Installing
  1. Create telegram bot with [BotFather](https://t.me/BotFather) and grab the token provided
  2. Send `/start` to your bot and open https://api.telegram.org/bot{$TELEGRAM_TOKEN_HERE}/getUpdates
  3. Take chat_id from response


Add hook on deploy:

```php
before('deploy', 'telegram:notify');
```

## Configuration

- `telegram_token` – telegram bot token, **required**
- `telegram_chat_id` — chat ID to push messages to
- `telegram_proxy` - proxy connection string in [CURLOPT_PROXY](https://curl.haxx.se/libcurl/c/CURLOPT_PROXY.html) form like:
  ```
  http://proxy:80
  socks5://user:password@host:3128
   ```
- `telegram_title` – the title of application, default `{{application}}`
- `telegram_text` – notification message template
  ```
  _{{user}}_ deploying `{{branch}}` to *{{target}}*
  ```
- `telegram_success_text` – success template, default:
  ```
  Deploy to *{{target}}* successful

  ```
- `telegram_failure_text` – failure template, default:
  ```
  Deploy to *{{target}}* failed
  ```

## Usage

If you want to notify only about beginning of deployment add this line only:

```php
before('deploy', 'telegram:notify');
```

If you want to notify about successful end of deployment add this too:

```php
after('deploy:success', 'telegram:notify:success');
```
If you want to notify about failed deployment add this too:

```php
after('deploy:failed', 'telegram:notify:failure');


 */
namespace Deployer;
use Deployer\Utility\Httpie;

// Title of project
set('telegram_title', function () {
    return get('application', 'Project');
});

// Telegram settings
set('telegram_token', function () {
    throw new \Exception('Please, configure "telegram_token" parameter.');
});
set('telegram_chat_id', function () {
    throw new \Exception('Please, configure "telegram_chat_id" parameter.');
});
set('telegram_url', function () {
   return 'https://api.telegram.org/bot' . get('telegram_token') . '/sendmessage';
});

// Deploy message
set('telegram_text', '_{{user}}_ deploying `{{branch}}` to *{{target}}*');
set('telegram_success_text', 'Deploy to *{{target}}* successful');
set('telegram_failure_text', 'Deploy to *{{target}}* failed');


desc('Notifies Telegram');
task('telegram:notify', function () {
    if (!get('telegram_token', false)) {
        warning('No Telegram token configured');
        return;
    }

    if (!get('telegram_chat_id', false)) {
        warning('No Telegram chat id configured');
        return;
    }

    $telegramUrl = get('telegram_url') . '?' . http_build_query (
        Array (
            'chat_id' => get('telegram_chat_id'),
            'text' => get('telegram_text'),
            'parse_mode' => 'Markdown',
        )
    );

    $httpie = Httpie::get($telegramUrl);

    if (get('telegram_proxy', '') !== '') {
        $httpie = $httpie->setopt(CURLOPT_PROXY, get('telegram_proxy'));
    }

    $httpie->send();
})
    ->once()
    ->hidden();

  desc('Notifies Telegram about deploy finish');
  task('telegram:notify:success', function () {
      if (!get('telegram_token', false)) {
          warning('No Telegram token configured');
          return;
      }

      if (!get('telegram_chat_id', false)) {
          warning('No Telegram chat id configured');
          return;
      }

      $telegramUrl = get('telegram_url') . '?' . http_build_query (
          Array (
              'chat_id' => get('telegram_chat_id'),
              'text' => get('telegram_success_text'),
              'parse_mode' => 'Markdown',
          )
      );

      $httpie = Httpie::get($telegramUrl);

      if (get('telegram_proxy', '') !== '') {
          $httpie = $httpie->setopt(CURLOPT_PROXY, get('telegram_proxy'));
      }

      $httpie->send();
})
    ->once()
    ->hidden();

  desc('Notifies Telegram about deploy failure');
  task('telegram:notify:failure', function () {
    if (!get('telegram_token', false)) {
        warning('No Telegram token configured');
        return;
    }

    if (!get('telegram_chat_id', false)) {
        warning('No Telegram chat id configured');
        return;
    }

      $telegramUrl = get('telegram_url') . '?' . http_build_query (
          Array (
              'chat_id' => get('telegram_chat_id'),
              'text' => get('telegram_failure_text'),
              'parse_mode' => 'Markdown',
          )
      );

      $httpie = Httpie::get($telegramUrl);

      if (get('telegram_proxy', '') !== '') {
          $httpie = $httpie->setopt(CURLOPT_PROXY, get('telegram_proxy'));
      }

      $httpie->send();
})
    ->once()
    ->hidden();
