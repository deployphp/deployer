<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Utility\Httpie;

// Title of project based on git repo
set('cimonitor_title', function () {
    $repo = get('repository');
    $pattern = '/\w+\/\w+/';
    return preg_match($pattern, $repo, $titles) ? $titles[0] : $repo;
});
set('cimonitor_user', function () {
    return [
      'name' => runLocally('git config --get user.name'),
      'email' => runLocally('git config --get user.email'),
    ];
});

// CI monitor status states and job states
set('cimonitor_status_info', 'info');
set('cimonitor_status_warning', 'warning');
set('cimonitor_status_error', 'error');
set('cimonitor_status_success', 'success');
set('cimonitor_job_state_info', get('cimonitor_status_info'));
set('cimonitor_job_state_pending', 'pending');
set('cimonitor_job_state_running', 'running');
set('cimonitor_job_state_warning', get('cimonitor_status_warning'));
set('cimonitor_job_state_error', get('cimonitor_status_error'));
set('cimonitor_job_state_success', get('cimonitor_status_success'));

desc('Notifying CIMonitor');
task('cimonitor:notify', function () {
    if (!get('cimonitor_webhook', false)) {
        return;
    }

    $body = [
        'state' => get('cimonitor_status_warning'),
        'branch' => get('branch'),
        'title' => get('cimonitor_title'),
        'user' => get('cimonitor_user'),
        'stages' => [get('stage', '')],
        'jobs' => [
            [
                'name' => 'Deploying...',
                'stage' => '',
                'state' => get('cimonitor_job_state_running'),
            ]
        ],
    ];

    Httpie::post(get('cimonitor_webhook'))->body($body)->send();
})
    ->once()
    ->shallow()
    ->setPrivate();

desc('Notifying CIMonitor about deploy finish');
task('cimonitor:notify:success', function () {
    if (!get('cimonitor_webhook', false)) {
        return;
    }

    $depstage = 'Deployed to '.get('stage', '');

    $body = [
        'state' => get('cimonitor_status_success'),
        'branch' => get('branch'),
        'title' => get('cimonitor_title'),
        'user' => get('cimonitor_user'),
        'stages' => [$depstage],
        'jobs' => [
            [
                'name' => 'Deploy',
                'stage' => $depstage,
                'state' => get('cimonitor_job_state_success'),
            ]
        ],
    ];

    Httpie::post(get('cimonitor_webhook'))->body($body)->send();
})
    ->once()
    ->shallow()
    ->setPrivate();

desc('Notifying CIMonitor about deploy failure');
task('cimonitor:notify:failure', function () {
    if (!get('cimonitor_webhook', false)) {
        return;
    }

    $body = [
        'state' => get('cimonitor_status_error'),
        'branch' => get('branch'),
        'title' => get('cimonitor_title'),
        'user' => get('cimonitor_user'),
        'stages' => [get('stage', '')],
        'jobs' => [
            [
                'name' => 'Deploy',
                'stage' => '',
                'state' => get('cimonitor_job_state_error'),
            ]
        ],
    ];

    Httpie::post(get('cimonitor_webhook'))->body($body)->send();
})
    ->once()
    ->shallow()
    ->setPrivate();

