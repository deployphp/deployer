<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Host\Host;
use Deployer\Task\Context;

/*
 * Environment test
 */

task('test_env', function () {
    add('env', ['KEY' => 'env value']);

    writeln(run('echo $KEY $EXT', ['env' => [
        'EXT' => 'ext'
    ]]));

    writeln(runLocally('echo $KEY $LOCAL', ['env' => [
        'LOCAL' => 'local'
    ]]));
});


/*
 * Invoke test
 */

task('test_invoke', function () {
    invoke('test_invoke:subtask1');
});

task('test_invoke:subtask1', function () {
    writeln('first');
    invoke('test_invoke:subtask2');
});

task('test_invoke:subtask2', function () {
    writeln('second');
});


/*
 * Invoke group test
 */

task('test_invoke_group', function () {
    invoke('test_invoke_group:group');
});

task('test_invoke_group:group', [
    'test_invoke_group:subtask1',
    'test_invoke_group:subtask2',
]);

task('test_invoke_group:subtask1', function () {
    writeln('first');
});

task('test_invoke_group:subtask2', function () {
    writeln('second');
});


/*
 * Function "on" test
 */

localhost('test_on[01:05]')
    ->set('hostname', function () {
        return Context::get()->getHost()->getHostname();
    })
    ->set('deploy_path', __DIR__ . '/tmp/{{hostname}}')
    ->set('roles', ['test_on_roles']);


task('test_on', function () {
    on(roles('test_on_roles'), function (Host $host) {
        writeln('<' . ($host->getHostname() === get('hostname') ? 'yes:' : 'no:') . '{{hostname}}' . '>');
    });
})->local();
