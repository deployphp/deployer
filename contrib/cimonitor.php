<?php
/*
Monitor your deployments on [CIMonitor](https://github.com/CIMonitor/CIMonitor).

![CIMonitorGif](https://www.steefmin.xyz/deployer-example.gif)


Add tasks on deploy:

```php
before('deploy', 'cimonitor:notify');
after('deploy:success', 'cimonitor:notify:success');
after('deploy:failed', 'cimonitor:notify:failure');
```

## Configuration

- `cimonitor_webhook` – CIMonitor server webhook url, **required**
  ```
  set('cimonitor_webhook', 'https://cimonitor.enrise.com/webhook/deployer');
  ```
- `cimonitor_title` – the title of application, default the username\reponame combination from `{{repository}}`
  ```
  set('cimonitor_title', '');
  ```
- `cimonitor_user` – User object with name and email, default gets information from `git config`
  ```
  set('cimonitor_user', function () {
    return [
      'name' => 'John Doe',
      'email' => 'john@enrise.com',
    ];
  });
  ```

Various cimonitor statusses are set, in case you want to change these yourselves. See the [CIMonitor documentation](https://cimonitor.readthedocs.io/en/latest/) for the usages of different states.

## Usage

If you want to notify only about beginning of deployment add this line only:

```php
before('deploy', 'cimonitor:notify');
```

If you want to notify about successful end of deployment add this too:

```php
after('deploy:success', 'cimonitor:notify:success');
```

If you want to notify about failed deployment add this too:

```php
after('deploy:failed', 'cimonitor:notify:failure');
```
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

desc('Notifies CIMonitor');
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

    Httpie::post(get('cimonitor_webhook'))->jsonBody($body)->send();
})
    ->once()
    ->hidden();

desc('Notifies CIMonitor about deploy finish');
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

    Httpie::post(get('cimonitor_webhook'))->jsonBody($body)->send();
})
    ->once()
    ->hidden();

desc('Notifies CIMonitor about deploy failure');
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

    Httpie::post(get('cimonitor_webhook'))->jsonBody($body)->send();
})
    ->once()
    ->hidden();

