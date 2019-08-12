<?php
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

    Httpie::post(get('discord_webhook'))->body($message)->send();
});

// Tasks
desc('Just notify your Discord channel with all messages, without deploying');
task('discord:test', function () {
    set('discord_message', 'discord_notify_text');
    invoke('discord_send_message');
    set('discord_message', 'discord_success_text');
    invoke('discord_send_message');
    set('discord_message', 'discord_failure_text');
    invoke('discord_send_message');
})
    ->once()
    ->shallow();

desc('Notify Discord');
task('discord:notify', function () {
    set('discord_message', 'discord_notify_text');
    invoke('discord_send_message');
})
    ->once()
    ->shallow()
    ->isPrivate();

desc('Notify Discord about deploy finish');
task('discord:notify:success', function () {
    set('discord_message', 'discord_success_text');
    invoke('discord_send_message');
})
    ->once()
    ->shallow()
    ->isPrivate();

desc('Notify Discord about deploy failure');
task('discord:notify:failure', function () {
    set('discord_message', 'discord_failure_text');
    invoke('discord_send_message');
})
    ->once()
    ->shallow()
    ->isPrivate();
