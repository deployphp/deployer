<?php
/* (c) Gonçalo Queirós <mail@goncaloqueiros.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Utility\Httpie;

// Deploy message
set('workplace_text', '_{{user}}_ deploying `{{branch}}` to *{{target}}*');
set('workplace_success_text', 'Deploy to *{{target}}* successful');
set('workplace_failure_text', 'Deploy to *{{target}}* failed');

// By default, create a new post for every message
set('workplace_edit_post', false);

desc('Notifying Workplace');
task('workplace:notify', function () {
    if (!get('workplace_webhook', false)) {
        return;
    }
    $url = get('workplace_webhook') . '&message=' . urlencode(get('workplace_text'));
    $response = Httpie::post($url)->getJson();

    if (get('workplace_edit_post', false)) {
        // Endpoint will be something like: https//graph.facebook.com/<POST_ID>?<QUERY_PARAMS>
        $url = sprintf(
            '%s://%s/%s?%s',
            parse_url(get('workplace_webhook'), PHP_URL_SCHEME),
            parse_url(get('workplace_webhook'), PHP_URL_HOST),
            $response['id'],
            parse_url(get('workplace_webhook'), PHP_URL_QUERY)
        );
        // Replace the webhook with a url that points to the created post
        set('workplace_webhook', $url);
    }
})
    ->once()
    ->shallow()
    ->hidden();

desc('Notifying Workplace about deploy finish');
task('workplace:notify:success', function () {
    if (!get('workplace_webhook', false)) {
        return;
    }
    $url = get('workplace_webhook') . '&message=' . urlencode(get('workplace_success_text'));
    return Httpie::post($url)->send();
})
    ->once()
    ->shallow()
    ->hidden();

desc('Notifying Workplace about deploy failure');
task('workplace:notify:failure', function () {
    if (!get('workplace_webhook', false)) {
        return;
    }
    $url = get('workplace_webhook') . '&message=' . urlencode(get('workplace_failure_text'));
    return Httpie::post($url)->send();
})
    ->once()
    ->shallow()
    ->hidden();
