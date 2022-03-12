<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

task('update_manifest', function () {
    $version = get('version');
    $manifestPath = '~/deployer.org/artifacts/manifest.json';
    $manifest = json_decode(run("cat $manifestPath"), true);

    $newPharManifest = [
        'name' => 'deployer.phar',
        'sha1' => get('sha1'),
        'url' => "https://github.com/deployphp/deployer/releases/download/v$version/deployer.phar",
        'version' => $version,
    ];

    // Check if this version already in manifest.json.
    $alreadyExistVersion = null;
    foreach ($manifest as $i => $m) {
        if ($m['version'] === $version) {
            $alreadyExistVersion = $i;
        }
    }

    // Save or update.
    if (empty($alreadyExistVersion)) {
        $manifest[] = $newPharManifest;
    } else {
        $manifest[$alreadyExistVersion] = $newPharManifest;
    }

    // Write manifest to manifest.json.
    $content = json_encode($manifest, JSON_PRETTY_PRINT);
    run("echo '$content' > $manifestPath");
});
