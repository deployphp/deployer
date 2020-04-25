<?php
/* (c) Stephan Wentz <stephan@wentz.it>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Utility\Httpie;

set('hipchat_color', 'green');
set('hipchat_from', '{{target}}');
set('hipchat_message', '_{{user}}_ deploying `{{branch}}` to *{{target}}*');
set('hipchat_url', 'https://api.hipchat.com/v1/rooms/message');

desc('Notifying Hipchat channel of deployment');
task('hipchat:notify', function () {
    $params = [
        'room_id' => get('hipchat_room_id'),
        'from' => get('target'),
        'message' => get('hipchat_message'),
        'color' => get('hipchat_color'),
        'auth_token' => get('hipchat_token'),
        'notify' => 0,
        'format' => 'json',
    ];

    Httpie::get(get('hipchat_url'))
        ->query($params)
        ->send();
});
