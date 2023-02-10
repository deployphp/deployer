<?php
namespace Deployer;

use Deployer\Exception\Exception;
use Symfony\Component\Console\Helper\Table;

// The name of the release.
set('release_name', function () {
    return within('{{deploy_path}}', function () {
        $latest = run('cat .dep/latest_release || echo 0');
        return strval(intval($latest) + 1);
    });
});

// Holds releases log from `.dep/releases_log` file.
set('releases_log', function () {
    cd('{{deploy_path}}');

    if (!test('[ -f .dep/releases_log ]')) {
        return [];
    }

    $releaseLogs = array_map(function ($line) {
        return json_decode($line, true);
    }, explode("\n", run('tail -n 300 .dep/releases_log')));

    return array_filter($releaseLogs); // Return all non-empty lines.
});

// Return list of release names on host.
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

    // Return releases from newest to oldest.
    $releasesLog = array_reverse(get('releases_log'));

    $releases = [];
    foreach ($releasesLog as $release) {
        if (in_array($release['release_name'], $ll, true)) {
            $releases[] = $release['release_name'];
        }
    }
    return $releases;
});

// Return release path.
set('release_path', function () {
    $releaseExists = test('[ -h {{deploy_path}}/release ]');
    if ($releaseExists) {
        $link = run("readlink {{deploy_path}}/release");
        return substr($link, 0, 1) === '/' ? $link : get('deploy_path') . '/' . $link;
    } else {
        throw new Exception(parse('The "release_path" ({{deploy_path}}/release) does not exist.'));
    }
});

// Current release revision. Usually a git hash.
set('release_revision', function () {
    return run('cat {{release_path}}/REVISION');
});

// Return the release path during a deployment
// but fallback to the current path otherwise.
set('release_or_current_path', function () {
    $releaseExists = test('[ -h {{deploy_path}}/release ]');
    return $releaseExists ? get('release_path') : get('current_path');
});

// Clean up unfinished releases and prepare next release
desc('Prepares release');
task('deploy:release', function () {
    cd('{{deploy_path}}');

    // Clean up if there is unfinished release.
    if (test('[ -h release ]')) {
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
        $freeReleaseName = '...';
        // Check what $releaseName is integer.
        if (ctype_digit($releaseName)) {
            $freeReleaseName = intval($releaseName);
            // Find free release name.
            while (test("[ -d releases/$freeReleaseName ]")) {
                $freeReleaseName++;
            }
        }
        throw new Exception("Release name \"$releaseName\" already exists.\nRelease name can be overridden via:\n dep deploy -o release_name=$freeReleaseName");
    }

    // Save release_name.
    if (is_numeric($releaseName) && is_integer(intval($releaseName))) {
        run("echo $releaseName > .dep/latest_release");
    }

    // Metainfo.
    $timestamp = timestamp();
    $metainfo = [
        'created_at' => $timestamp,
        'release_name' => $releaseName,
        'user' => get('user'),
        'target' => get('target'),
    ];

    // Save metainfo about release.
    $json = escapeshellarg(json_encode($metainfo));
    run("echo $json >> .dep/releases_log");

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

desc('Shows releases list');
/*
 * Example output:
 * ```
 * +---------------------+------example.org ------------+--------+-----------+
 * | Date (UTC)          | Release     | Author         | Target | Commit    |
 * +---------------------+-------------+----------------+--------+-----------+
 * | 2021-11-06 20:51:45 | 1           | Anton Medvedev | HEAD   | 34d24192e |
 * | 2021-11-06 21:00:50 | 2 (bad)     | Anton Medvedev | HEAD   | 392948a40 |
 * | 2021-11-06 23:19:20 | 3           | Anton Medvedev | HEAD   | a4057a36c |
 * | 2021-11-06 23:24:30 | 4 (current) | Anton Medvedev | HEAD   | s3wa45ca6 |
 * +---------------------+-------------+----------------+--------+-----------+
 * ```
 */
task('releases', function () {
    cd('{{deploy_path}}');

    $releasesLog = get('releases_log');
    $currentRelease = basename(run('readlink {{current_path}}'));
    $releasesList = get('releases_list');

    $table = [];
    $tz = !empty(getenv('TIMEZONE')) ? getenv('TIMEZONE') : date_default_timezone_get();

    foreach ($releasesLog as &$metainfo) {
        $date = \DateTime::createFromFormat(\DateTimeInterface::ISO8601, $metainfo['created_at']);
        $date->setTimezone(new \DateTimeZone($tz));
        $status = $release = $metainfo['release_name'];
        if (in_array($release, $releasesList, true)) {
            if (test("[ -f releases/$release/BAD_RELEASE ]")) {
                $status = "<error>$release</error> (bad)";
            } else if (test("[ -f releases/$release/DIRTY_RELEASE ]")) {
                $status = "<error>$release</error> (dirty)";
            } else {
                $status = "<info>$release</info>";
            }
        }
        if ($release === $currentRelease) {
            $status .= ' (current)';
        }
        try {
            $revision = run("cat releases/$release/REVISION");
        } catch (\Throwable $e) {
            $revision = 'unknown';
        }
        $table[] = [
            $date->format("Y-m-d H:i:s"),
            $status,
            $metainfo['user'],
            $metainfo['target'],
            $revision,
        ];
    }

    (new Table(output()))
        ->setHeaderTitle(currentHost()->getAlias())
        ->setHeaders(["Date ($tz)", 'Release', 'Author', 'Target', 'Commit'])
        ->setRows($table)
        ->render();
});
