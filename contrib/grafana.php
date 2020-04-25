<?php
/* (c) beeete2 <beeete2@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Utility\Httpie;


desc('Create Grafana annotation of deployment');
task('grafana:annotation', function () {
    $defaultConfig = [
        'url' => null,
        'token' => null,
        'time' => round(microtime(true) * 1000),
        'tags' => [],
        'text' => null,
    ];

    $config = array_merge($defaultConfig, (array) get('grafana'));
    if (!is_array($config) || !isset($config['url']) || !isset($config['token'])) {
        throw new \RuntimeException("Please configure Grafana: set('grafana', ['url' => 'https://localhost/api/annotations', token' => 'eyJrIjo...']);");
    }

    $params = [
        'time' => $config['time'],
        'isRegion' => false,
        'tags' => $config['tags'],
        'text' => $config['text'],
    ];
    if (!isset($params['text'])) {
        $params['text'] = 'Deployed ' . trim(runLocally('git log -n 1 --format="%h"'));
    }

    Httpie::post($config['url'])
        ->header('Authorization: Bearer ' . $config['token'])
        ->header('Content-type: application/json')
        ->body($params)
        ->send();
});
