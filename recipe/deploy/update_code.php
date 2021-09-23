<?php
namespace Deployer;

use Deployer\Exception\RunException;

/**
 * Determines which branch to deploy. Can be overridden with cli option `--branch`.
 * If not specified, will get current git HEAD branch as default branch to deploy.
 */
set('branch', function () {
    if (input()->hasOption('branch') && !empty(input()->getOption('branch'))) {
        return input()->getOption('branch');
    }
    return null;
});

/**
 * Update code at {{release_path}} on host.
 */
desc('Update code');
task('deploy:update_code', function () {
    $repository = get('repository');
    $branch = get('branch');
    $git = get('bin/git');

    $at = 'HEAD';
    if (!empty($branch)) {
        $at = $branch;
    }

    // If option `tag` is set.
    if (input()->hasOption('tag')) {
        $tag = input()->getOption('tag');
        if (!empty($tag)) {
            $at = $tag;
        }
    }

    // If option `tag` is not set and option `revision` is set.
    if (empty($tag)) {
        $revision = input()->getOption('revision');
        if (!empty($revision)) {
            $at = $revision;
        }
    }

    $url = parse_url($repository);

    if (isset($url['scheme']) && $url['scheme'] === 'ssh') {
        $repositoryHostname = $url['host'];
        $portOptions = $url['port'] !== 22 ? "-p {$url['port']}" : null;
        try {
            run("ssh-keygen -F $repositoryHostname");
        } catch (RunException $e) {
            run("ssh-keyscan $portOptions -H $repositoryHostname >> ~/.ssh/known_hosts");
        }
    }

    $bare = parse('{{deploy_path}}/.dep/repo');

    start:
    // Clone the repository to a bare repo.
    run("[ -d $bare ] || mkdir -p $bare");
    run("[ -f $bare/HEAD ] || $git clone --mirror $repository $bare 2>&1");

    cd($bare);

    // If remote url changed, drop `.dep/repo` and reinstall.
    if (run("$git config --get remote.origin.url") !== $repository) {
        cd('{{deploy_path}}');
        run("rm -rf $bare");
        goto start;
    }

    // Copy to release_path.
    run("$git remote update 2>&1");
    run("$git archive $at | tar -x -f - -C {{release_path}} 2>&1");

    // Save revision in .dep and in variable for later usage in scripts.
    $rev = escapeshellarg(run("$git rev-list $at -1"));
    run("echo $rev > {{release_path}}/REVISION");
});
