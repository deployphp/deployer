<?php
namespace Deployer;

use Deployer\Exception\Exception;

/*
 * Rollback candidate will be automatically chosen by looking
 * at output of `ls` command and content of `.dep/releases_log`.
 *
 * If rollback candidate is marked as **BAD_RELEASE**, it will be skipped.
 *
 * :::tip
 * You can override rollback candidate via:
 * ```
 * dep rollback -o rollback_candidate=123
 * ```
 * :::
 */
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

desc('Rollbacks to the previous release');
/*
 * Uses {{rollback_candidate}} for symlinking. Current release will be marked as
 * bad by creating file **BAD_RELEASE** with timestamp and {{user}}.
 *
 * :::warning
 * You can always manually symlink {{current_path}} to proper release.
 * ```
 * dep run '{{bin/symlink}} releases/123 {{current_path}}'
 * ```
 * :::
 */
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
    $timestamp = timestamp();
    run("echo '$timestamp,{{user}}' > releases/$currentRelease/BAD_RELEASE");

    writeln("<info>rollback</info> to release <info>$candidate</info> was <success>successful</success>");
});
