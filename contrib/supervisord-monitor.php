<?php
/*
### Description
This is a recipe that uses the [Supervisord server monitoring project](https://github.com/mlazarov/supervisord-monitor).

With this recipe the possibility is created to restart a supervisord process through the Supervisor Monitor webtool, by using cURL. This workaround is particular usefull when the deployment user has unsuficient rights to restart a daemon process from the cli.

### Configuration

```
set('supervisord', [
    'uri' => 'https://youruri.xyz/supervisor',
    'basic_auth_user' => 'username',
    'basic_auth_password' => 'password',
    'process_name' => 'process01',
]);
```

or

```
set('supervisord_uri', 'https://youruri.xyz/supervisor');
set('supervisord_basic_auth_user', 'username');
set('supervisord_basic_auth_password', 'password');
set('supervisord_process_name', 'process01');
```

- `supervisord` – array with configuration for Supervisord
    - `uri` – URI to the Supervisord monitor page
    - `basic_auth_user` – Basic auth username to access the URI
    - `basic_auth_password` – Basic auth password to access the URI
    - `process_name` – the process name, as visible in the Supervisord monitor page. Multiple processes can be listed here, comma separated

### Task

- `supervisord-monitor:restart` Restarts given processes
- `supervisord-monitor:stop` Stops given processes
- `supervisord-monitor:start` Starts given processes

### Usage

A complete example with configs, staging and deployment

```
<?php

namespace Deployer;
use Dotenv\Dotenv;

require 'vendor/autoload.php';

require 'supervisord_monitor.php';

// Project name
set('application', 'myproject.com');

// Project repository
set('repository', 'git@github.com:myorg/myproject.com');

set('supervisord', [
    'uri' => 'https://youruri.xyz/supervisor',
    'basic_auth_user' => 'username',
    'basic_auth_password' => 'password',
    'process_name' => 'process01',
]);

host('staging.myproject.com')
    ->set('branch', 'develop')
    ->set('labels', ['stage' => 'staging']);

host('myproject.com')
    ->set('branch', 'main')
    ->set('labels', ['stage' => 'production']);

// Tasks
task('build', function () {
    run('cd {{release_path}} && build');
});

task('deploy', [
    'build',
    'supervisord',
]);

task('supervisord', ['supervisord-monitor:restart'])
    ->select('stage=production');
```
*/
namespace Deployer;

use Deployer\Utility\Httpie;

function supervisordCheckConfig()
{
    $config = get('supervisord', []);
    foreach ($config as $key => $value) {
        if ($value) {
            set('supervisord_' . $key, $value);
        }
    }

    if (!get('supervisord_uri') ||
        !get('supervisord_basic_auth_user') ||
        !get('supervisord_basic_auth_password') ||
        !get('supervisord_process_name')) {
        throw new \RuntimeException("<comment>Please configure Supervisord config:</comment> <info>set('supervisord', array('uri' => 'yourdomain.xyz/supervisor', 'basic_auth_user' => 'abc' , 'basic_auth_password' => 'xyz', 'process_name' => 'process01,process02'));</info> or <info>set('supervisord_uri', 'yourdomain.xyz/supervisor'); set('supervisord_basic_auth_user', 'abc'); etc</info>");
    }
}

function supervisordGetBasicAuthToken()
{
    return 'Basic ' . base64_encode(get('supervisord_basic_auth_user'). ':'. get('supervisord_basic_auth_password'));
}

function supervisordIsAuthenticated()
{
    supervisordCheckConfig();

    $authResponseInfo = [];
    Httpie::post(get('supervisord_uri'))->header('Authorization', supervisordGetBasicAuthToken())->send($authResponseInfo);

    return $authResponseInfo['http_code'] === 200;
}

function supervisordControlAction($name, $action = 'stop')
{
    $stopResponseInfo = [];
    Httpie::post(get('supervisord_uri') . '/control/'.$action.'/localhost/'.$name)->header('Authorization', supervisordGetBasicAuthToken())->send($stopResponseInfo);

    return $stopResponseInfo['http_code'] === 200;
}

task('supervisord-monitor:restart', function () {
    if (supervisordIsAuthenticated()) {
        $names = explode(',', get('supervisord_process_name'));
        foreach ($names as $name) {
            $name = trim($name);
            if (supervisordControlAction($name, 'stop')) {
                writeln('Daemon ['.$name.'] stopped');
                if (supervisordControlAction($name, 'start')) {
                    writeln('Daemon ['.$name.'] started');
                }
            }
        }
    } else {
        writeln('Authentication failed');
    }
});

task('supervisord-monitor:stop', function () {
    if (supervisordIsAuthenticated()) {
        $names = explode(',', get('supervisord_process_name'));
        foreach ($names as $name) {
            $name = trim($name);
            if (supervisordControlAction($name, 'stop')) {
                writeln('Daemon ['.$name.'] stopped');
            }
        }
    } else {
        writeln('Authentication failed');
    }
});

task('supervisord-monitor:start', function () {
    if (supervisordIsAuthenticated()) {
        $names = explode(',', get('supervisord_process_name'));
        foreach ($names as $name) {
            $name = trim($name);
            if (supervisordControlAction($name, 'start')) {
                writeln('Daemon ['.$name.'] started');
            }
        }
    } else {
        writeln('Authentication failed');
    }
});
