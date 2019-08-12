<?php
/* (c) Laurent Goussard <loranger@free.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Utility\Httpie;

set('rollbar_comment', '_{{user}}_ deploying `{{branch}}` to *{{target}}*');

desc('Notifying Rollbar of deployment');
task('rollbar:notify', function () {
    if (!get('rollbar_token', false)) {
        return;
    }

    $params = [
        'access_token' => get('rollbar_token'),
        'environment' => get('target'),
        'revision' => runLocally('git log -n 1 --format="%h"'),
        'local_username' => get('user'),
        'rollbar_username' => get('rollbar_username'),
        'comment' => get('rollbar_comment'),
    ];

    Httpie::post('https://api.rollbar.com/api/1/deploy/')
        ->form($params)
        ->send();
})
    ->once()
    ->shallow();
