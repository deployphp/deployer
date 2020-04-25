<?php
/* (c) Marek Grudzinski <mail@marek-grudzinski.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Utility\Httpie;

set('mattermost_webhook', null);
set('mattermost_channel', null);
set('mattermost_username', 'deployer');
set('mattermost_icon_url', null);

set('mattermost_success_emoji', ':white_check_mark:');
set('mattermost_failure_emoji', ':x:');

set('mattermost_text', '_{{user}}_ deploying `{{branch}}` to **{{target}}**');
set('mattermost_success_text', 'Deploy to **{{target}}** successful {{mattermost_success_emoji}}');
set('mattermost_failure_text', 'Deploy to **{{target}}** failed {{mattermost_failure_emoji}}');

desc('Notify mattermost');
task('mattermost:notify', function() {
    if (null === get('mattermost_webhook')) {
        return;
    }

    $body = [
        'text' => get('mattermost_text'),
        'username' => get('mattermost_username'),
    ];

    if (get('mattermost_channel')) {
        $body['channel'] = get('mattermost_channel');
    }
    if (get('mattermost_icon_url')) {
        $body['icon_url'] = get('mattermost_icon_url');
    }

    Httpie::post(get('mattermost_webhook'))->body($body)->send();
});

desc('Notifying mattermost about deploy finish');
task('mattermost:notify:success', function() {
    if (null === get('mattermost_webhook')) {
        return;
    }

    $body = [
        'text' => get('mattermost_success_text'),
        'username' => get('mattermost_username'),
    ];

    if (get('mattermost_channel')) {
        $body['channel'] = get('mattermost_channel');
    }
    if (get('mattermost_icon_url')) {
        $body['icon_url'] = get('mattermost_icon_url');
    }

    Httpie::post(get('mattermost_webhook'))->body($body)->send();
});

desc('Notifying mattermost about deploy failure');
task('mattermost:notify:failure', function() {
    if (null === get('mattermost_webhook')) {
        return;
    }

    $body = [
        'text' => get('mattermost_failure_text'),
        'username' => get('mattermost_username'),
    ];

    if (get('mattermost_channel')) {
        $body['channel'] = get('mattermost_channel');
    }
    if (get('mattermost_icon_url')) {
        $body['icon_url'] = get('mattermost_icon_url');
    }

    Httpie::post(get('mattermost_webhook'))->body($body)->send();
});
