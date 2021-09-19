<?php

namespace Deployer;

use Deployer\Exception\Exception;
use Symfony\Component\Console\Helper\Table;

/**
 * The name of the release.
 */
set('release_name', function () {
    $latest = run('cat .dep/latest_release || echo 0');
    return strval(intval($latest) + 1);
});

/**
 * Holds metainfo about releases from `.dep/releases_metainfo` file.
 */
set('releases_metainfo', function () {
    cd('{{deploy_path}}');

    if (!test('[ -f .dep/releases_metainfo ]')) {
        return [];
    }

    $keepReleases = get('keep_releases');
    if ($keepReleases === -1) {
        $data = run('cat .dep/releases_metainfo');
    } else {
        $data = run("tail -n " . ($keepReleases + 5) . " .dep/releases_metainfo");
    }

    $releasesMetainfo = [];
    foreach (explode("\n", $data) as $line) {
        $metainfo = json_decode($line, true);
        if (!empty($metainfo)) {
            $releasesMetainfo[] = $metainfo;
        }
    }
    return $releasesMetainfo;
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

    $releasesMetainfo = get('releases_metainfo');

    $releases = [];
    for ($i = count($releasesMetainfo) - 1; $i >= 0; --$i) {
        $release = $releasesMetainfo[$i]['release_name'];
        if (in_array($release, $ll, true)) {
            $releases[] = $release;
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

/**
 * Return the release path during a deployment
 * but fallback to the current path otherwise.
 */
set('release_or_current_path', function () {
    $releaseExists = test('[ -h {{deploy_path}}/release ]');
    return $releaseExists ? get('release_path') : get('current_path');
});

desc('Prepare release. Clean up unfinished releases and prepare next release');
task('deploy:release', function () {
    cd('{{deploy_path}}');

    // Clean up if there is unfinished release.
    if (test('[ -h release ]')) {
        run('rm -rf "$(readlink release)"'); // Delete release.
        run('rm release'); // Delete symlink.
    }

    // We need to get releases_list at same point as release_name,
    // as standard release_name's implementation depends on it and,
    // if user overrides it, we need to get releases_list manually.
    $releasesList = get('releases_list');
    $releaseName = get('release_name');
    $releasePath = "releases/$releaseName";

    // Check what there is no such release path.
    if (test("[ -d $releasePath ]")) {
        throw new Exception("Release name \"$releaseName\" already exists.");
    }

    // Metainfo.
    $timestamp = (new \DateTime('now', new \DateTimeZone('UTC')))->format(\DateTime::ISO8601);
    $metainfo = [
        'created_at' => $timestamp,
        'release_name' => $releaseName,
        'user' => get('user'),
        'target' => get('target'),
        'revision' => "__REVISION__"
    ];

    // Save metainfo about release.
    $json = json_encode($metainfo);
    run("echo '$json' >> .dep/releases_metainfo");
    run("echo '$releaseName' > .dep/latest_release");

    // Make new release.
    run("mkdir -p $releasePath");
    run("{{bin/symlink}} $releasePath {{deploy_path}}/release");

    // Add to releases list.
    array_unshift($releasesList, $releaseName);
    set('releases_list', $releasesList);

    // Set previous_release.
    if (isset($releasesList[1])) {
        set('previous_release', "{{deploy_path}}/releases/{$releasesList[1]}");
    }
});

desc('Show releases list');
task('releases', function () {
    cd('{{deploy_path}}');

    $releasesMetainfo = get('releases_metainfo');
    $currentRelease = basename(run('readlink {{current_path}}'));
    $releasesList = get('releases_list');

    $table = [];
    foreach ($releasesMetainfo as &$metainfo) {
        $status = $release = $metainfo['release_name'];
        if (in_array($release, $releasesList, true)) {
            if (test("[ -f releases/$release/BAD_RELEASE ]")) {
                $status = "<error>$release</error> (bad)";
            } else {
                $status = "<info>$release</info>";
            }
        }
        if ($release === $currentRelease) {
            $status .= ' (current)';
        }
        $table[] = [
            \DateTime::createFromFormat(\DateTime::ISO8601, $metainfo['created_at'])->format("Y-m-d H:i:s"),
            $status,
            $metainfo['user'],
            $metainfo['target'],
            $metainfo['revision'],
        ];
    }

    (new Table(output()))
        ->setHeaderTitle(currentHost()->getAlias())
        ->setHeaders(['Date', 'Release', 'Author', 'Target', 'Commit'])
        ->setRows($table)
        ->render();
});
