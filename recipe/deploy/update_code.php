<?php
namespace Deployer;

use Deployer\Exception\RunException;

/**
 * Determines which branch to deploy. Can be overridden with cli option `--branch`.
 * If not specified, will get current git HEAD branch as default branch to deploy.
 */
set('branch', function () {
    try {
        $branch = runLocally('git rev-parse --abbrev-ref HEAD');
    } catch (\Throwable $exception) {
        $branch = null;
    }

    if ($branch === 'HEAD') {
        $branch = null; // Travis-CI fix
    }

    if (input()->hasOption('branch') && !empty(input()->getOption('branch'))) {
        $branch = input()->getOption('branch');
    }

    return $branch;
});

/**
 * This config option will if set to true will instructs git to use previous release files,
 * and download only changed files from server.
 *
 * You don't need to set this option, it will automatically detect if your git supports this feature.
 *
 * Faster cloning by borrowing objects from existing clones.
 */
set('git_cache', function () {
    $gitVersion = run('{{bin/git}} version');
    $regs = [];
    if (preg_match('/((\d+\.?)+)/', $gitVersion, $regs)) {
        $version = $regs[1];
    } else {
        $version = "1.0.0";
    }
    return version_compare($version, '2.3', '>=');
});

/**
 * Update code at {{release_path}} on host.
 */
desc('Update code');
task('deploy:update_code', function () {
    $repository = get('repository');
    $branch = get('branch');
    $git = get('bin/git');
    $gitCache = get('git_cache');
    $recursive = get('git_recursive', true) ? '--recursive' : '';
    $dissociate = get('git_clone_dissociate', true) ? '--dissociate' : '';
    $quiet = output()->isQuiet() ? '-q' : '';
    $depth = $gitCache ? '' : '--depth 1';

    $at = '';
    if (!empty($branch)) {
        $at = "-b \"$branch\"";
    }

    // If option `tag` is set
    if (input()->hasOption('tag')) {
        $tag = input()->getOption('tag');
        if (!empty($tag)) {
            $at = "-b \"$tag\"";
        }
    }

    // If option `tag` is not set and option `revision` is set
    if (empty($tag) && input()->hasOption('revision')) {
        $revision = input()->getOption('revision');
        if (!empty($revision)) {
            $depth = '';
        }
    }

    // Enter deploy_path if present
    if (has('deploy_path')) {
        cd('{{deploy_path}}');
    }

    // Populate known hosts
    preg_match('/.*(@|\/\/)([^\/:]+).*/', $repository, $match);
    if (isset($match[2])) {
        $repositoryHostname = $match[2];
        try {
            run("ssh-keygen -F $repositoryHostname");
        } catch (RunException $exception) {
            run("ssh-keyscan -H $repositoryHostname >> ~/.ssh/known_hosts");
        }
    }

    if ($gitCache && has('previous_release')) {
        try {
            run("$git clone $at $recursive $quiet --reference {{previous_release}} $dissociate $repository  {{release_path}} 2>&1");
        } catch (\Throwable $exception) {
            // If {{deploy_path}}/releases/{$releases[1]} has a failed git clone, is empty, shallow etc, git would throw error and give up. So we're forcing it to act without reference in this situation
            run("$git clone $at $recursive $quiet $repository {{release_path}} 2>&1");
        }
    } else {
        // if we're using git cache this would be identical to above code in catch - full clone. If not, it would create shallow clone.
        run("$git clone $at $depth $recursive $quiet $repository {{release_path}} 2>&1");
    }

    if (!empty($revision)) {
        run("cd {{release_path}} && $git checkout $revision");
    }
});
