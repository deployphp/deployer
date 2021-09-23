<?php

namespace Deployer;

use Deployer\Exception\Exception;

desc('');
set('rollback_candidate', function () {
    $currentRelease = basename(run('readlink {{current_path}}'));
    $releases = get('releases_list');

    $releasesBeforeCurrent = [];
    $foundCurrent = false;
    foreach ($releases as $r) {
        if ($r === $currentRelease) {
            $foundCurrent = true;
            continue;
        }
        if ($foundCurrent) {
            $releasesBeforeCurrent[] = $r;
        }
    }

    while (isset($releasesBeforeCurrent[0])) {
        $candidate = $releasesBeforeCurrent[0];

        // Skip all bad releases.
        if (test("[ -f {{deploy_path}}/releases/$candidate/BAD_RELEASE ]")) {
            array_shift($releasesBeforeCurrent);
            continue;
        }

        return $candidate;
    }

    throw new Exception("No more releases you can revert to.");
});

desc('Rollback to previous release');
task('rollback', function () {
    cd('{{deploy_path}}');

    $currentRelease = basename(run('readlink {{current_path}}'));
    $candidate = get('rollback_candidate');

    writeln("Current release is <fg=red>$currentRelease</fg=red>.");

    if (!test("[ -d releases/$candidate ]")) {
        throw new \RuntimeException(parse("Release \"$candidate\" not found in \"{{deploy_path}}/releases\"."));
    }
    if (test("[ -f releases/$candidate/BAD_RELEASE ]")) {
        writeln("Candidate <fg=yellow>$candidate</> marked as <error>bad release</error>.");
        if (!askConfirmation("Continue rollback to $candidate?")) {
            writeln('Rollback aborted.');
            return;
        }
    }
    writeln("Rolling back to <info>$candidate</info> release.");

    // Symlink to old release.
    run("{{bin/symlink}} releases/$candidate {{current_path}}");

    // Mark release as bad.
    $timestamp = (new \DateTime('now', new \DateTimeZone('UTC')))->format(\DateTime::ISO8601);
    run("echo '$timestamp,{{user}}' > releases/$currentRelease/BAD_RELEASE");

    writeln("<info>rollback</info> to release <info>$candidate</info> was <success>successful</success>");
});
