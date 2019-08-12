<?php
/* (c) Raz <raz@eviladmin.xyz>
 * Based on Slack nofifier recipe by Anton Medvedev
 * Configuration:
    1. Create telegram bot by any manual in the internet
    2. Take telegrambot token (Like: 123456789:SOME_STRING) and set `telegram_token` parameter
    3. Send /start to your bot, open https://api.telegram.org/bot{telegram_token}/getUpdates
    4. Take chat_id from response, and set `telegram_chat_id` parameter
    5. If you want, you can edit `telegram_text`, `telegram_success_text` or `telegram_failure_text`
    6. Profit!
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


desc('Notifying Telegram');

task('telegram:notify', function () {
    if (!get('telegram_token', false)) {
        return;
    }
    
    if (!get('telegram_chat_id', false)) {
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
    ->shallow()
    ->setPrivate();

  desc('Notifying Telegram about deploy finish');
  task('telegram:notify:success', function () {
      if (!get('telegram_token', false)) {
          return;
      }
      
      if (!get('telegram_chat_id', false)) {
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
    ->shallow()
    ->setPrivate();

  desc('Notifying Telegram about deploy failure');
  task('telegram:notify:failure', function () {
      if (!get('telegram_token', false)) {
          return;
      }
      
      if (!get('telegram_chat_id', false)) {
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
    ->shallow()
    ->setPrivate();
