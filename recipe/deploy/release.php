<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Type\Csv;

set('release_name', function () {
    $list = get('releases_list');

    // Filter out anything that does not look like a release.
    $list = array_filter($list, function ($release) {
        return preg_match('/^[\d\.]+$/', $release);
    });

    $nextReleaseNumber = 1;
    if (count($list) > 0) {
        $nextReleaseNumber = (int)max($list) + 1;
    }

    return (string)$nextReleaseNumber;
}); // name of folder in releases

/**
 * Return list of releases on server.
 */
set('releases_list', function () {
    cd('{{deploy_path}}');

    // If there is no releases return empty list.
    if (!run('[ -d releases ] && [ "$(ls -A releases)" ] && echo "true" || echo "false"')->toBool()) {
        return [];
    }

    // Will list only dirs in releases.
    $list = run('cd releases && ls -t -d */')->toArray();

    // Prepare list.
    $list = array_map(function ($release) {
        return basename(rtrim($release, '/'));
    }, $list);

    $releases = []; // Releases list.

    // Collect releases based on .dep/releases info.
    // Other will be ignored.

    if (run('if [ -f .dep/releases ]; then echo "true"; fi')->toBool()) {
        $keepReleases = get('keep_releases');
        if ($keepReleases === -1) {
            $csv = run('cat .dep/releases');
        } else {
            // Instead of `tail -n` call here can be `cat` call,
            // but on servers with a lot of deploys (more 1k) it
            // will output a really big list of previous releases.
            // It spoils appearance of output log, to make it pretty,
            // we limit it to `n*2 + 5` lines from end of file (15 lines).
            $csv = run("tail -n " . ($keepReleases * 2 + 5) . " .dep/releases");
        }

        $metainfo = Csv::parse($csv);

        for ($i = count($metainfo) - 1; $i >= 0; --$i) {
            if (is_array($metainfo[$i]) && count($metainfo[$i]) >= 2) {
                list($date, $release) = $metainfo[$i];
                $index = array_search($release, $list, true);
                if ($index !== false) {
                    $releases[] = $release;
                    unset($list[$index]);
                }
            }
        }
    }

    return $releases;
});

/**
 * Return release path.
 */
set('release_path', function () {
    $releaseExists = run("if [ -h {{deploy_path}}/release ]; then echo 'true'; fi")->toBool();
    if ($releaseExists) {
        $link = run("readlink {{deploy_path}}/release")->toString();
        return substr($link, 0, 1) === '/' ? $link : get('deploy_path') . '/' . $link;
    } else {
        return get('current_path');
    }
});


desc('Prepare release');
task('deploy:release', function () {
    cd('{{deploy_path}}');

    // Clean up if there is unfinished release.
    $previousReleaseExist = run("if [ -h release ]; then echo 'true'; fi")->toBool();

    if ($previousReleaseExist) {
        run('rm -rf "$(readlink release)"'); // Delete release.
        run('rm release'); // Delete symlink.
    }

    $releaseName = get('release_name');

    // Fix collisions.
    $i = 0;
    while (run("if [ -d {{deploy_path}}/releases/$releaseName ]; then echo 'true'; fi")->toBool()) {
        $releaseName .= '.' . ++$i;
        set('release_name', $releaseName);
    }

    $releasePath = parse("{{deploy_path}}/releases/{{release_name}}");

    // Metainfo.
    $date = run('date +"%Y%m%d%H%M%S"');

    // Save metainfo about release.
    run("echo '$date,{{release_name}}' >> .dep/releases");

    // Make new release.
    run("mkdir $releasePath");
    run("{{bin/symlink}} $releasePath {{deploy_path}}/release");
});
