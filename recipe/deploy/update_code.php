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
    $repository_dirs = get('repository_dirs');

    $repository_dirs_src = implode(' ', $repository_dirs);

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

    // Populate known hosts.
    preg_match('/.*(@|\/\/)([^\/:]+).*/', $repository, $match);
    if (isset($match[2])) {
        $repositoryHostname = $match[2];
        try {
            run("ssh-keygen -F $repositoryHostname");
        } catch (RunException $e) {
            run("ssh-keyscan -H $repositoryHostname >> ~/.ssh/known_hosts");
        }
    }

    $bare = parse('{{deploy_path}}/.dep/repo');

    start:
    // Clone the repository to a bare repo.
    run("[ -d $bare ] || mkdir -p $bare");
    run("[ -f $bare/HEAD ] || $git clone --mirror $repository $bare 2>&1");

    cd($bare);

    // If remote url changed, drop `.git/repo` and reinstall.
    if (run("$git config --get remote.origin.url") !== $repository) {
        cd('{{deploy_path}}');
        run("rm -rf $bare");
        goto start;
    }

    // Copy to release_path.
    run("$git remote update 2>&1");
    run("$git archive $at $repository_dirs_src | tar -x -f - -C {{release_path}} 2>&1");

    // Save revision in releases log.
    $rev = run("$git rev-list $at -1");
    run("sed -ibak 's/revision/$rev/' {{deploy_path}}/.dep/releases");
});
