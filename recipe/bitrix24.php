<?php
/**
 * (c) Alexander Kalchenko <supergulman09@gmail.com>
 */

namespace Deployer;

use Deployer\Utility\Httpie;

set('bitrix_text', '_{{user}}_ deploying [I]{{branch}}[/I] to [B]{{target}}[/B]');

desc('Notifying Bitrix24');
task('bitrix:notify', function () {
    if (!get('bitrix_webhook', false)) {
        return;
    }

    $params = [
        'BOT_ID' => get('bitrix_bot_id'),
        'CLIENT_ID' => get('bitrix_client_id'),
        'DIALOG_ID' => get('bitrix_chat_id'),
        'MESSAGE' => get('bitrix_text'),
    ];

    Httpie::get(get('bitrix_webhook'))->query($params)->send();
})
    ->once()
    ->shallow()
    ->setPrivate();

desc('Notifying Bitrix24 about deploy finish');
task('bitrix:notify:success', function () {
    if (!get('bitrix_webhook', false)) {
        return;
    }

    $params = [
        'BOT_ID' => get('bitrix_bot_id'),
        'CLIENT_ID' => get('bitrix_client_id'),
        'DIALOG_ID' => get('bitrix_chat_id'),
        'MESSAGE' => get('bitrix_success_text'),
    ];

    Httpie::get(get('bitrix_webhook'))->query($params)->send();
})
    ->once()
    ->shallow()
    ->setPrivate();

desc('Notifying Bitrix24 about deploy failure');
task('bitrix:notify:failure', function () {
    if (!get('bitrix_webhook', false)) {
        return;
    }

    $params = [
        'BOT_ID' => get('bitrix_bot_id'),
        'CLIENT_ID' => get('bitrix_client_id'),
        'DIALOG_ID' => get('bitrix_chat_id'),
        'MESSAGE' => get('bitrix_failure_text'),
    ];

    Httpie::get(get('bitrix_webhook'))->query($params)->send();
})
    ->once()
    ->shallow()
    ->setPrivate();
