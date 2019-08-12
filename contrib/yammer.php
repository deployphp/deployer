<?php
/* (c) beeete2 <beeete2@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Utility\Httpie;

set('yammer_url', 'https://www.yammer.com/api/v1/messages.json');

// Title of project
set('yammer_title', function () {
    return get('application', 'Project');
});

// Deploy message
set('yammer_body', '<em>{{user}}</em> deploying {{branch}} to <strong>{{target}}</strong>');
set('yammer_success_body', 'Deploy to <strong>{{target}}</strong> successful');
set('yammer_failure_body', 'Deploy to <strong>{{target}}</strong> failed');

desc('Notifying Yammer');
task('yammer:notify', function () {
    $params = [
        'is_rich_text' => 'true',
        'message_type' => 'announcement',
        'group_id' => get('yammer_group_id'),
        'title' => get('yammer_title'),
        'body' => get('yammer_body'),
    ];

    Httpie::post(get('yammer_url'))
        ->header('Authorization: Bearer ' . get('yammer_token'))
        ->header('Content-type: application/json')
        ->body($params)
        ->send();
})
    ->once()
    ->shallow()
    ->setPrivate();

desc('Notifying Yammer about deploy finish');
task('yammer:notify:success', function () {
    $params = [
        'is_rich_text' => 'true',
        'message_type' => 'announcement',
        'group_id' => get('yammer_group_id'),
        'title' => get('yammer_title'),
        'body' => get('yammer_success_body'),
    ];

    Httpie::post(get('yammer_url'))
        ->header('Authorization: Bearer ' . get('yammer_token'))
        ->header('Content-type: application/json')
        ->body($params)
        ->send();
})
    ->once()
    ->shallow()
    ->setPrivate();

desc('Notifying Yammer about deploy failure');
task('yammer:notify:failure', function () {
    $params = [
        'is_rich_text' => 'true',
        'message_type' => 'announcement',
        'group_id' => get('yammer_group_id'),
        'title' => get('yammer_title'),
        'body' => get('yammer_failure_body'),
    ];

    Httpie::post(get('yammer_url'))
        ->header('Authorization: Bearer ' . get('yammer_token'))
        ->header('Content-type: application/json')
        ->body($params)
        ->send();
})
    ->once()
    ->shallow()
    ->setPrivate();
