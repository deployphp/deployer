<?php

namespace Deployer;

use Deployer\Exception\Exception;
use Deployer\Support\Csv;

/**
 * Name of folder in releases.
 */
set('release_name', function () {
    $list = array_map(function ($r) {
        return $r[1];
    }, get('releases_metainfo'));

    // Filter out anything that does not look like a number.
    $list = array_filter($list, function ($release) {
        return preg_match('/^\d+$/', $release);
    });

    $nextReleaseNumber = 1;
    if (count($list) > 0) {
        $nextReleaseNumber = (int)max($list) + 1;
    }

    return (string)$nextReleaseNumber;
});

/**
 * Holds metainfo about releases from `.dep/releases` file.
 */
set('releases_metainfo', function () {
    cd('{{deploy_path}}');

    if (!test('[ -f .dep/releases ]')) {
        return [];
    }

    $keepReleases = get('keep_releases');
    if ($keepReleases === -1) {
        $csv = run('cat .dep/releases');
    } else {
        $csv = run("tail -n " . ($keepReleases + 5) . " .dep/releases");
    }
    return Csv::parse($csv);
});

/**
 * Return list of releases on host.
 */
set('releases_list', function () {
    cd('{{deploy_path}}');

    // If there is no releases return empty list.
    if (!test('[ -d releases ] && [ "$(ls -A releases)" ]')) {
        return [];
    }

    // Will list only dirs in releases.
    $ll = explode("\n", run('cd releases && ls -t -1 -d */'));
    $ll = array_map(function ($release) {
        return basename(rtrim(trim($release), '/'));
    }, $ll);

    $metainfo = get('releases_metainfo');

    $releases = [];
    for ($i = count($metainfo) - 1; $i >= 0; --$i) {
        if (is_array($metainfo[$i]) && count($metainfo[$i]) >= 2) {
            list(, $release) = $metainfo[$i];
            if (in_array($release, $ll, true)) {
                $releases[] = $release;
            }
        }
    }
    return $releases;
});

/**
 * Return release path.
 */
set('release_path', function () {
    $releaseExists = test('[ -h {{deploy_path}}/release ]');
    if ($releaseExists) {
        $link = run("readlink {{deploy_path}}/release");
        return substr($link, 0, 1) === '/' ? $link : get('deploy_path') . '/' . $link;
    } else {
        throw new Exception(parse('The "release_path" ({{deploy_path}}/release) does not exist.'));
    }
});


desc('Prepare release. Clean up unfinished releases and prepare next release');
task('deploy:release', function () {
    cd('{{deploy_path}}');

    // Clean up if there is unfinished release
    if (test('[ -h release ]')) {
        run('rm -rf "$(readlink release)"'); // Delete release
        run('rm release'); // Delete symlink
    }

    // We need to get releases_list at same point as release_name,
    // as standard release_name's implementation depends on it and,
    // if user overrides it, we need to get releases_list manually.
    $releasesList = get('releases_list');
    $releaseName = get('release_name');

    // Fix collisions
    $i = 0;
    while (test("[ -d {{deploy_path}}/releases/$releaseName ]")) {
        $releaseName .= '.' . ++$i;
        set('release_name', $releaseName);
    }

    $releasePath = parse("{{deploy_path}}/releases/{{release_name}}");

    // Metainfo.
    $date = run('date +"%Y%m%d%H%M%S"');

    // Save metainfo about release
    run("echo '$date,{{release_name}},{{user}},{{target}}' >> .dep/releases");

    // Make new release
    run("mkdir -p $releasePath");
    run("{{bin/symlink}} $releasePath {{deploy_path}}/release");

    // Add to releases list
    array_unshift($releasesList, $releaseName);
    set('releases_list', $releasesList);

    // Set previous_release
    if (isset($releasesList[1])) {
        set('previous_release', "{{deploy_path}}/releases/{$releasesList[1]}");
    }
});
