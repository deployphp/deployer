<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

task('update_banner', function () {
    $skipCurrentVersion = get('version');
    $manifestPath = '~/deployer.org/artifacts/manifest.json';
    $manifest = json_decode(run("cat $manifestPath"), true);
    $commands = '';
    foreach ($manifest as $m) {
        if ($skipCurrentVersion === $m['version']) {
            continue;
        }
        $commands .= "echo -n '{{banner}}' > ~/deployer.org/check-updates/{$m['version']};\n";
    }
    run($commands);
});
