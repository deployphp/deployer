<?php
namespace Deployer;

use Deployer\Exception\ConfigurationException;

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

// Automatically populate `known_hosts` file based on {{repository}} config.
set('auto_ssh_keygen', true);

// Sets deploy:update_code strategy.
// Can we one of:
// - archive
// - checkout (if you need `.git` dir in your {{release_path}})
set('update_code_strategy', 'archive');

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

    if (get('auto_ssh_keygen')) {
        $url = parse_url($repository);
        if (isset($url['scheme']) && $url['scheme'] === 'ssh') {
            $host = $url['host'];
            $port = $url['port'] ?? '22';
        } else if (preg_match('/(?:@|\/\/)([^\/:]+)(?:\:(\d{1,5}))?/', $repository, $matches)) {
            $host = $matches[1];
            $port = $matches[2] ?? '22';
        } else {
            warning("Can't parse repository url ($repository).");
        }
        if (isset($host) && isset($port)) {
            run("ssh-keygen -F $host:$port || ssh-keyscan -p $port -H $host >> ~/.ssh/known_hosts");
        } else {
            warning("Please, make sure your server can clone the repo.");
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

    run("$git remote update 2>&1");

    // Copy to release_path.
    if (get('update_code_strategy') === 'archive') {
        run("$git archive $at | tar -x -f - -C {{release_path}} 2>&1");
    } else if (get('update_code_strategy') === 'checkout') {
        run("$git --work-tree {{release_path}} checkout --force $at");
    } else {
        throw new ConfigurationException(parse("Unknown `update_code_strategy` option: {{update_code_strategy}}."));
    }

    // Save git revision in REVISION file.
    $rev = escapeshellarg(run("$git rev-list $at -1"));
    run("echo $rev > {{release_path}}/REVISION");
});
