<?php
/**
 * With ❤ by Alexander Kalchenko <supergulman09@gmail.com>
 */

/*
# Usage example
```php
// setting core configuration
set('bitrix_webhook', 'https://mybitrix.bitrix24.ru/rest/111/5w9s2qatn1xqtkve/imbot.message.add.json');
set('bitrix_bot_id', 1111);
set('bitrix_client_id', 'q94zphhebaob8h1yymwzs3xun69gpkf6');
set('bitrix_chat_id', 'chat11111');

// setting messages
set('bitrix_text', 'Запустили деплой на сервер.');
set('bitrix_success_text', 'Деплой на сервер успешно завершён.');
set('bitrix_failure_text', 'Деплой на сервер не удался.');

// setting tasks when messages will be send to bitrix
before('deploy:prepare', 'bitrix:notify');
after('success', 'bitrix:notify:success');
after('deploy:failed', 'bitrix:notify:failure');
```
# Configuration
### Webhook configuration
```php
set('bitrix_webhook', 'https://mybitrix.bitrix24.ru/rest/111/5w9s2qatn1xqtkve/imbot.message.add.json');
```
After webhook URL, need set method, that will reseve message, in this case:
`https://mybitrix.bitrix24.ru/rest/111/5w9s2qatn1xqtkve/` - URL,
`imbot.message.add.json` - method

### Configuration BOT_ID
```php
set('bitrix_bot_id', 1111);
```
Here setting bot id, that will reseve messages from webhook and send to bitrix chat.
Bot creates in Bitrix24 CRM

### Configuration CHAT_ID
```php
set('bitrix_chat_id', 'chat11111');
```
Here setting chat id where messages will be displayed.
In Bitrix24 we can get chat id by typing `/getDialogId` in chat we need.

### Configuration CLIENT_ID
```php
set('bitrix_client_id', 'q7lzphdnugrb8h1ymamhs8xun34gkvf6');
```
Here setting client id, that we got after bot creation.

# Message types
`bitrix_text` - Text for message, that will be send when deploy starts
`bitrix_success_text` - Text for message, that wil be send after success deploy
`bitrix_failure_text` - Text for message, that wil be send after failure deploy

# Tasks for sending messages
`bitrix:notify` - Task for send message about deploy start
`bitrix:notify:success` - Task for send message about success deploy
`bitrix:notify:failure` - Task for send message about failure deploy

## Русскоязычная документация на https://github.com/iSanyok/php-deployer-bitrix24
*/
namespace Deployer;

use Deployer\Utility\Httpie;

/**
 * Sends message to Bitrix24
 *
 * @param string $msg
 * @return void
 */
function sendMessage(string $msg): void
{
    $params = [
        'BOT_ID' => get('bitrix_bot_id'),
        'CLIENT_ID' => get('bitrix_client_id'),
        'DIALOG_ID' => get('bitrix_chat_id'),
        'MESSAGE' => $msg,
    ];

    Httpie::get(get('bitrix_webhook'))->query($params)->send();
}

/**
 * Sets 'bitrix_message' if it
 */
set('bitrix_text', '_{{user}}_ deploying [I]{{branch}}[/I] to [B]{{target}}[/B]');

/**
 * Sends message before deploying
 */
desc('Notifying Bitrix24');
task('bitrix:notify', function () {
    if (!get('bitrix_webhook', false)) {
        return;
    }

    sendMessage(get('bitrix_text'));
})
    ->once()
    ->shallow()
    ->setPrivate();

/**
 * Sends message after successful deploy
 */
desc('Notifying Bitrix24 about deploy finish');
task('bitrix:notify:success', function () {
    if (!get('bitrix_webhook', false)) {
        return;
    }

    sendMessage(get('bitrix_success_text'));
})
    ->once()
    ->shallow()
    ->setPrivate();

/**
 * Sends message after failure deploy
 */
desc('Notifying Bitrix24 about deploy failure');
task('bitrix:notify:failure', function () {
    if (!get('bitrix_webhook', false)) {
        return;
    }

    sendMessage(get('bitrix_failure_text'));
})
    ->once()
    ->shallow()
    ->setPrivate();
