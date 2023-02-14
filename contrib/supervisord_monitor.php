<?php

namespace Deployer;

use Deployer\Utility\Httpie;

set('supervisord_uri', function () {
    throw new \Exception('Please, configure "supervisord_uri" parameter.');
});

set('supervisord_basic_auth_user', function () {
    throw new \Exception('Please, configure "supervisord_basic_auth_user" parameter.');
});

set('supervisord_basic_auth_password', function () {
    throw new \Exception('Please, configure "supervisord_basic_auth_password" parameter.');
});

set('supervisord_process_name', function () {
    throw new \Exception('Please, configure "supervisord_process_name" parameter.');
});

function getBasicAuthToken() {
    return 'Basic ' . base64_encode(get('supervisord_basic_auth_user'). ':'. get('supervisord_basic_auth_password'));
}

function isAuthenticated() {
    $authResponseInfo = [];
    Httpie::post(get('supervisord_uri'))->header('Authorization',  getBasicAuthToken())->send($authResponseInfo);

    return $authResponseInfo['http_code'] === 200;
}

function action($name, $action = 'stop') {
    $stopResponseInfo = [];
    Httpie::post(get('supervisord_uri') . '/control/'.$action.'/localhost/'.$name)->header('Authorization',  getBasicAuthToken())->send($stopResponseInfo);

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
        $names = explode(',', get('supervisord_process_name'));
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
