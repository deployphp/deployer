<?php
/*
## Configuration

- `hipchat_token` – Hipchat V1 auth token
- `hipchat_room_id` – Room ID or name
- `hipchat_message` –  Deploy message, default is `_{{user}}_ deploying `{{branch}}` to *{{target}}*`
- `hipchat_from` – Default to target
- `hipchat_color` – Message color, default is **green**
- `hipchat_url` –  The URL to the message endpoint, default is https://api.hipchat.com/v1/rooms/message

## Usage

Since you should only notify Hipchat room of a successful deployment, the `hipchat:notify` task should be executed right at the end.

```php
after('deploy', 'hipchat:notify');
```

 */
namespace Deployer;

use Deployer\Utility\Httpie;

set('hipchat_color', 'green');
set('hipchat_from', '{{target}}');
set('hipchat_message', '_{{user}}_ deploying `{{branch}}` to *{{target}}*');
set('hipchat_url', 'https://api.hipchat.com/v1/rooms/message');

desc('Notifies Hipchat channel of deployment');
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
