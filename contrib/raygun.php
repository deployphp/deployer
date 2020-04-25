<?php
/* (c) Matt Byers <matt@raygun.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Utility\Httpie;

desc('Notifying Raygun of deployment');
task('raygun:notify', function () {
    $data = [
        'apiKey'       => get('raygun_api_key'),
        'version' => get('raygun_version'),
        'ownerName'   => get('raygun_owner_name'),
        'emailAddress' => get('raygun_email'),
        'comment' => get('raygun_comment'),
        'scmIdentifier' => get('raygun_scm_identifier'),
        'scmType' => get('raygun_scm_type')
    ];

    Httpie::post('https://app.raygun.io/deployments')
        ->body($data)
        ->send();
});