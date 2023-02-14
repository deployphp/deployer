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

- `supervisord` – array with configuration for Supervisord
    - `uri` – URI to the Supervisord monitor page
    - `basic_auth_user` – Basic auth username to access the URI
    - `basic_auth_password` – Basic auth password to access the URI
    - `process_name` – the process name, as visible in the Supervisord monitor page. Multiple processes can be listed here, comma separated
   
### Task

- `supervisord-monitor:restart` Restarts given processes

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
    ->set('labels' => ['stage' => 'staging']);

host('myproject.com')
    ->set('branch', 'main')
    ->set('labels' => ['stage' => 'production']);

// Tasks
task('build', function () {
    run('cd {{release_path}} && build');
});

task('deploy', [
    'build',
    'supervisord',
])

task('supervisord', ['supervisord-monitor:restart'])
    ->select('stage=production');

```

 */
namespace Deployer;

use Deployer\Utility\Httpie;

function getConfig() {
    $config = get('supervisord', []);

    if (!is_array($config) ||
    !isset($config['uri']) ||
    !isset($config['basic_auth_user']) ||
    !isset($config['basic_auth_password']) ||
    !isset($config['process_name'])) {
    throw new \RuntimeException("<comment>Please configure Supervisord config:</comment> <info>set('supervisord', array('uri' => 'yourdomain.xyz/supervisor', 'basic_auth_user' => 'abc' , 'basic_auth_password' => 'xyz', 'process_name' => 'process01,process02'));</info>");
}
}

function getBasicAuthToken() {
    return 'Basic ' . base64_encode(get('supervisord')['basic_auth_user']. ':'. get('supervisord')['basic_auth_password']);
}

function isAuthenticated() {
    getConfig();

    $authResponseInfo = [];
    Httpie::post(get('supervisord')['uri'])->header('Authorization',  getBasicAuthToken())->send($authResponseInfo);

    return $authResponseInfo['http_code'] === 200;
}

function action($name, $action = 'stop') {
    $stopResponseInfo = [];
    Httpie::post(get('supervisord')['uri'] . '/control/'.$action.'/localhost/'.$name)->header('Authorization',  getBasicAuthToken())->send($stopResponseInfo);

    return $stopResponseInfo['http_code'] === 200;
}

function stop($name) {
    return action($name, 'stop');
}

function start($name) {
    return action($name, 'start');
}

task('supervisord-monitor:restart', function() {
    if(isAuthenticated()) {
        $names = explode(',', get('supervisord')['process_name']);
        foreach($names as $name) {
            $name = trim($name);
            if(stop($name)) {
                writeln('Daemon ['.$name.'] stopped');
                if(start($name)) {
                    writeln('Daemon ['.$name.'] started');
                }
            }
        }
    }
});



