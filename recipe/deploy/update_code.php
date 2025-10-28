<?php

namespace Deployer;

use Deployer\Exception\ConfigurationException;
use Symfony\Component\Console\Input\InputOption;

/**
 * Determines which branch to deploy. Can be overridden with CLI option `--branch`.
 * If not specified, will get current git HEAD branch as default branch to deploy.
 */
set('branch', 'HEAD');

option('tag', null, InputOption::VALUE_REQUIRED, 'Tag to deploy');
option('revision', null, InputOption::VALUE_REQUIRED, 'Revision to deploy');
option('branch', null, InputOption::VALUE_REQUIRED, 'Branch to deploy');

// The deploy target: a branch, a tag or a revision.
set('target', function () {
    $target = '';

    $branch = get('branch');
    if (!empty($branch)) {
        $target = $branch;
    }

    // Override target from CLI options.
    if (input()->hasOption('branch') && !empty(input()->getOption('branch'))) {
        $target = input()->getOption('branch');
    }
    if (input()->hasOption('tag') && !empty(input()->getOption('tag'))) {
        $target = input()->getOption('tag');
    }
    if (input()->hasOption('revision') && !empty(input()->getOption('revision'))) {
        $target = input()->getOption('revision');
    }

    if (empty($target)) {
        $target = "HEAD";
    }
    return $target;
});

// Sets deploy:update_code strategy.
// Can be one of:
// - local_archive (copies the repository from local machine)
// - archive (default, fetches the code from the remote repository)
// - clone (if you need the origin repository `.git` dir in your {{release_path}}, clones from remote repository)
set('update_code_strategy', 'archive');

// Sets environment variable _GIT_SSH_COMMAND_ for `git clone` command.
// If `StrictHostKeyChecking` flag is set to `accept-new` then ssh will
// automatically add new host keys to the user known hosts files, but
// will not permit connections to hosts with changed host keys.
set('git_ssh_command', 'ssh -o StrictHostKeyChecking=accept-new');

/**
 * Specifies a sub directory within the repository to deploy.
 * Works only when [`update_code_strategy`](#update_code_strategy) is set to `archive` (default) or `local_archive`.
 *
 * Example:
 *  - set value to `src` if you want to deploy the folder that lives at `/src`.
 *  - set value to `src/api` if you want to deploy the folder that lives at `/src/api`.
 *
 * Note: do not use a leading `/`!
 */
set('sub_directory', false);

/**
 * Update code at {{release_path}} on host.
 */
desc('Updates code');
task('deploy:update_code', function () {
    $strategy = get('update_code_strategy');
    $target = get('target');
    $git = get('bin/git');

    $targetWithDir = $target;
    if (!empty(get('sub_directory'))) {
        $targetWithDir .= ':{{sub_directory}}';
    }

    if ($strategy === 'local_archive') {
        runLocally("$git archive $targetWithDir -o archive.tar");
        upload('archive.tar', '{{release_path}}/archive.tar');
        run("tar -xf {{release_path}}/archive.tar -C {{release_path}}");
        run("rm {{release_path}}/archive.tar");
        unlink('archive.tar');

        $rev = escapeshellarg(runLocally("git rev-list $target -1"));
    } else {
        $repository = get('repository');

        if (empty($repository)) {
            throw new ConfigurationException("Missing 'repository' configuration.");
        }

        $bare = parse('{{deploy_path}}/.dep/repo');
        $env = [
            'GIT_TERMINAL_PROMPT' => '0',
            'GIT_SSH_COMMAND' => get('git_ssh_command'),
        ];

        start:
        // Clone the repository to a bare repo.
        run("[ -d $bare ] || mkdir -p $bare");
        run("[ -f $bare/HEAD ] || $git clone --mirror $repository $bare 2>&1", env: $env);

        cd($bare);

        // If remote url changed, drop `.dep/repo` and reinstall.
        if (run("$git config --get remote.origin.url") !== $repository) {
            cd('{{deploy_path}}');
            run("rm -rf $bare");
            goto start;
        }

        run("$git remote update 2>&1", env: $env);

        // Copy to release_path.
        if ($strategy === 'archive') {
            run("$git archive $targetWithDir | tar -x -f - -C {{release_path}} 2>&1");
        } elseif ($strategy === 'clone') {
            cd('{{release_path}}');
            run("$git clone -l $bare .");
            run("$git remote set-url origin $repository", env: $env);
            run("$git checkout --force $target");
        } else {
            throw new ConfigurationException(parse("Unknown `update_code_strategy` option: {{update_code_strategy}}."));
        }

        $rev = escapeshellarg(run("$git rev-list $target -1"));
    }

    // Save git revision in REVISION file.
    run("echo $rev > {{release_path}}/REVISION");
});
