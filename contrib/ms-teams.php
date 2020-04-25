<?php
/* (c) Neven Kajić
 * Based on Slack notifier recipe by Anton Medvedev
 *
 * Setup:
 *   1.) Open MS Teams
 *   2.) Navigate to Teams section
 *   3.) Select existing or create new team
 *   4.) Select existing or create new channel
 *   5.) Hover over channel to get tree dots, click, in menu select "Connectors"
 *   6.) Search for and configure "Incoming Webhook"
 *   7.) Confirm/create and copy your Webhook URL
 *   8.) Setup deploy.php
 *       Add in header:
 *        require 'vendor/deployer/recipes/recipe/ms-teams.php';
 *        set('teams_webhook', '<YOUR_WEBHOOK_URL>');
 *       Add in content:
 *        before('deploy', 'teams:notify');
 *        after('success', 'teams:notify:success');
 *        after('deploy:failed', 'teams:notify:failure');
 *   9.) Sip your coffee
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Utility\Httpie;

// Title of project
set('teams_title', function () {
    return get('application', 'Project');
});

// Deploy message
set('teams_text', '_{{user}}_ deploying `{{branch}}` to *{{target}}*');
set('teams_success_text', 'Deploy to *{{target}}* successful');
set('teams_failure_text', 'Deploy to *{{target}}* failed');

// Color of attachment
set('teams_color', '#4d91f7');
set('teams_success_color', '#00c100');
set('teams_failure_color', '#ff0909');

desc('Notifying Teams');
task('teams:notify', function () {
    if (!get('teams_webhook', false)) {
        return;
    }

    Httpie::post(get('teams_webhook'))->body([
        "themeColor" => get('teams_color'),
        'text'       => get('teams_text')
    ])->send();
})
    ->once()
    ->shallow()
    ->hidden();

desc('Notifying Teams about deploy finish');
task('teams:notify:success', function () {
    if (!get('teams_webhook', false)) {
        return;
    }

    Httpie::post(get('teams_webhook'))->body([
        "themeColor" => get('teams_success_color'),
        'text'       => get('teams_success_text')
    ])->send();
})
    ->once()
    ->shallow()
    ->hidden();

desc('Notifying Teams about deploy failure');
task('teams:notify:failure', function () {
    if (!get('teams_webhook', false)) {
        return;
    }

    Httpie::post(get('teams_webhook'))->body([
        "themeColor" => get('teams_failure_color'),
        'text'       => get('teams_failure_text')
    ])->send();
})
    ->once()
    ->shallow()
    ->hidden();
