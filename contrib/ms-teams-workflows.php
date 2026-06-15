<?php
/*
## Installing

Microsoft is retiring the legacy "Incoming Webhook" connector. New
integrations must use the **Workflows** app (Power Automate). This
recipe targets the Workflows "Post to a channel when a webhook request
is received" template.

Setup:
1. Open MS Teams
2. Navigate to Teams section
3. Select existing or create new team
4. Select existing or create new channel
5. Click the three dots on the channel, choose "Workflows"
6. Pick the template **Post to a channel when a webhook request is received**
7. Follow the wizard, then copy the generated HTTPS POST URL
8. Setup deploy.php
    Add in header:
```php
require 'contrib/ms-teams-workflows.php';
set('teams_workflows_webhook', 'https://prod-XX.westeurope.logic.azure.com:443/workflows/...');
```
Add in content:
```php
before('deploy', 'teams-workflows:notify');
after('deploy:success', 'teams-workflows:notify:success');
after('deploy:failed', 'teams-workflows:notify:failure');
```
9.) Sip your coffee

## Configuration

- `teams_workflows_webhook` – workflow HTTPS POST URL, **required**
  ```
  set('teams_workflows_webhook', 'https://prod-XX.westeurope.logic.azure.com:443/workflows/...');
  ```
- `teams_workflows_title` – the title of application, default `{{application}}`
- `teams_workflows_text` – notification message template
  ```
  set('teams_workflows_text', '_{{user}}_ deploying `{{what}}` to *{{where}}*');
  ```
- `teams_workflows_success_text` – success template, default:
  ```
  set('teams_workflows_success_text', 'Deploy to *{{where}}* successful');
  ```
- `teams_workflows_failure_text` – failure template, default:
  ```
  set('teams_workflows_failure_text', 'Deploy to *{{where}}* failed');
  ```
- `teams_workflows_failure_continue` – if `true`, errors talking to the
  workflow endpoint are downgraded to warnings instead of aborting the
  deploy. Default `false`.

## Usage

If you want to notify only about beginning of deployment add this line only:

```php
before('deploy', 'teams-workflows:notify');
```

If you want to notify about successful end of deployment add this too:

```php
after('deploy:success', 'teams-workflows:notify:success');
```

If you want to notify about failed deployment add this too:

```php
after('deploy:failed', 'teams-workflows:notify:failure');
```
 */

namespace Deployer;

use Deployer\Utility\Httpie;

// Title of project
set('teams_workflows_title', function () {
    return get('application', 'Project');
});

// Allow Continue on Failure
set('teams_workflows_failure_continue', false);

// Deploy message
set('teams_workflows_text', '_{{user}}_ deploying `{{what}}` to *{{where}}*');
set('teams_workflows_success_text', 'Deploy to *{{where}}* successful');
set('teams_workflows_failure_text', 'Deploy to *{{where}}* failed');

desc('Notifies Teams (Workflows)');
task('teams-workflows:notify', function () {
    if (!get('teams_workflows_webhook', false)) {
        warning('No MS Teams Workflows webhook configured');
        return;
    }

    try {
        Httpie::post(get('teams_workflows_webhook'))->jsonBody([
            'text' => get('teams_workflows_text'),
        ])->send();
    } catch (\Exception $e) {
        if (get('teams_workflows_failure_continue', false)) {
            warning('Error sending Teams Workflows Notification: ' . $e->getMessage());
        } else {
            throw $e;
        }
    }
})
    ->once()
    ->hidden();

desc('Notifies Teams (Workflows) about deploy finish');
task('teams-workflows:notify:success', function () {
    if (!get('teams_workflows_webhook', false)) {
        warning('No MS Teams Workflows webhook configured');
        return;
    }

    try {
        Httpie::post(get('teams_workflows_webhook'))->jsonBody([
            'text' => get('teams_workflows_success_text'),
        ])->send();
    } catch (\Exception $e) {
        if (get('teams_workflows_failure_continue', false)) {
            warning('Error sending Teams Workflows Notification: ' . $e->getMessage());
        } else {
            throw $e;
        }
    }
})
    ->once()
    ->hidden();

desc('Notifies Teams (Workflows) about deploy failure');
task('teams-workflows:notify:failure', function () {
    if (!get('teams_workflows_webhook', false)) {
        warning('No MS Teams Workflows webhook configured');
        return;
    }

    try {
        Httpie::post(get('teams_workflows_webhook'))->jsonBody([
            'text' => get('teams_workflows_failure_text'),
        ])->send();
    } catch (\Exception $e) {
        if (get('teams_workflows_failure_continue', false)) {
            warning('Error sending Teams Workflows Notification: ' . $e->getMessage());
        } else {
            throw $e;
        }
    }
})
    ->once()
    ->hidden();
