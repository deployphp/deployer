<?php
/* (c) Samuel Gordalina <samuel.gordalina@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Utility\Httpie;

set('newrelic_app_id', function () {
    throw new \Exception('Please, configure "newrelic_app_id" parameter.');
});

set('newrelic_description', function() {
    return runLocally('git log -n 1 --format="%an: %s" | tr \'"\' "\'"');
});

set('newrelic_revision', function() {
    return runLocally('git log -n 1 --format="%h"');
});

desc('Notifying New Relic of deployment');
task('newrelic:notify', function () {
    if (($appId = get('newrelic_app_id')) && ($apiKey = get('newrelic_api_key'))) {
        $data = [
            'user' => get('user'),
            'revision' => get('newrelic_revision'),
            'description' => get('newrelic_description'),
        ];

        Httpie::post("https://api.newrelic.com/v2/applications/$appId/deployments.json")
            ->header("X-Api-Key: $apiKey")
            ->query(['deployment' => $data])
            ->send();
    }
})
    ->once()
    ->shallow()
    ->setPrivate();
