<?php
/* (c) Tim Robertson <funkjedi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Utility\Httpie;

desc('Notifying Bugsnag of deployment');
task('bugsnag:notify', function () {
    $data = [
        'apiKey'       => get('bugsnag_api_key'),
        'releaseStage' => get('target'),
        'repository'   => get('repository'),
        'provider'     => get('bugsnag_provider', ''),
        'branch'       => get('branch'),
        'revision'     => runLocally('git log -n 1 --format="%h"'),
        'appVersion'   => get('bugsnag_app_version', ''),
    ];

    Httpie::post('https://notify.bugsnag.com/deploy')
        ->body($data)
        ->send();
});
