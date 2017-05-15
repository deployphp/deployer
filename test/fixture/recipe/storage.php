<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

// Hosts

use Deployer\Task\Context;

localhost('[a:f]')
    ->set('deploy_path', function () {
        return __DIR__ . run('echo {{hostname}}'); // Test what call to run possible during materialization process
    });

set('hostname', function () {
    return Context::get()->getHost()->getHostname();
});

// Tasks

task('test', ['set', 'get', 'tie']);

task('set', function () {
    on(host('[a:f]'), function ($host) {
        $host->set('value', '{{hostname}}');
    });
})->local();

task('get', function () {
    writeln("{{hostname}}:{{value}}");
    set('key', '{{hostname}}');
});

task('tie', function () {
    $value = '';
    on(host('[a:f]'), function () use (&$value) {
        $value .= parse('{{key}}');
    });
    writeln($value);
})->local();
