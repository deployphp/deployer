<?php
/*

## Configuration

- `rollbar_token` – access token to rollbar api
- `rollbar_comment` – comment about deploy, default to
  ```php
  set('rollbar_comment', '_{{user}}_ deploying `{{branch}}` to *{{target}}*');
  ```
- `rollbar_username` – rollbar user name

## Usage

Since you should only notify Rollbar channel of a successful deployment, the `rollbar:notify` task should be executed right at the end.

```php
after('deploy', 'rollbar:notify');
```

 */

namespace Deployer;

use Deployer\Utility\Httpie;

function checkConfig()
{
    $config = get('supervisord', []);
    foreach ($config as $key => $value) {
        set('supervisord_' . $key, $value);
    }

    if (!get('supervisord_uri') ||
        !get('supervisord_basic_auth_user') ||
        !get('supervisord_basic_auth_password') ||
        !get('supervisord_process_name')) {
        throw new \RuntimeException("<comment>Please configure Supervisord config:</comment> <info>set('supervisord', array('uri' => 'yourdomain.xyz/supervisor', 'basic_auth_user' => 'abc' , 'basic_auth_password' => 'xyz', 'process_name' => 'process01,process02'));</info> or <info>set('supervisord_uri', 'yourdomain.xyz/supervisor'); set('supervisord_basic_auth_user', 'abc'); etc</info>");
    }
}

function getBasicAuthToken()
{
    return 'Basic ' . base64_encode(get('supervisord_basic_auth_user'). ':'. get('supervisord_basic_auth_password'));
}

function isAuthenticated()
{
    checkConfig();

    $authResponseInfo = [];
    Httpie::post(get('supervisord_uri'))->header('Authorization', getBasicAuthToken())->send($authResponseInfo);

    return $authResponseInfo['http_code'] === 200;
}

function action($name, $action = 'stop')
{
    $stopResponseInfo = [];
    Httpie::post(get('supervisord')['uri'] . '/control/'.$action.'/localhost/'.$name)->header('Authorization', getBasicAuthToken())->send($stopResponseInfo);

    return $stopResponseInfo['http_code'] === 200;
}

function stop($name)
{
    return action($name, 'stop');
}

function start($name)
{
    return action($name, 'start');
}

task('supervisord-monitor:restart', function () {
    if (isAuthenticated()) {
        $names = explode(',', get('supervisord')['process_name']);
        foreach ($names as $name) {
            $name = trim($name);
            if (stop($name)) {
                writeln('Daemon ['.$name.'] stopped');
                if (start($name)) {
                    writeln('Daemon ['.$name.'] started');
                }
            }
        }
    }
});
