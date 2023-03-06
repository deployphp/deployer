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
// - archive
// - clone (if you need the origin repository `.git` dir in your {{release_path}})
set('update_code_strategy', 'archive');

// Sets environment variable _GIT_SSH_COMMAND_ for `git clone` command.
// If `StrictHostKeyChecking` flag is set to `accept-new` then ssh will
// automatically add new host keys to the user known hosts files, but
// will not permit connections to hosts with changed host keys.
set('git_ssh_command', 'ssh -o StrictHostKeyChecking=accept-new');

/**
 * Specifies a sub directory within the repository to deploy.
 * Works only when [`update_code_strategy`](#update_code_strategy) is set to `archive` (default).
 *
 * Example:
 *  - set value to `src` if you want to deploy the folder that lives at `/src/api`.
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
    $git = get('bin/git');
    $repository = get('repository');
    $target = get('target');

    $targetWithDir = $target;
    if (!empty(get('sub_directory'))) {
        $targetWithDir .= ':{{sub_directory}}';
    }

    $bare = parse('{{deploy_path}}/.dep/repo');
    $env = [
        'GIT_TERMINAL_PROMPT' => '0',
        'GIT_SSH_COMMAND' => get('git_ssh_command')
    ];

    start:
    // Clone the repository to a bare repo.
    run("[ -d $bare ] || mkdir -p $bare");
    run("[ -f $bare/HEAD ] || $git clone --mirror $repository $bare 2>&1", ['env' => $env]);

    cd($bare);

    // If remote url changed, drop `.dep/repo` and reinstall.
    if (run("$git config --get remote.origin.url") !== $repository) {
        cd('{{deploy_path}}');
        run("rm -rf $bare");
        goto start;
    }

    run("$git remote update 2>&1", ['env' => $env]);


    // Copy to release_path.
    if (get('update_code_strategy') === 'archive') {
        run("$git archive $targetWithDir | tar -x -f - -C {{release_path}} 2>&1");
    } else if (get('update_code_strategy') === 'clone') {
        cd('{{release_path}}');
        run("$git clone -l $bare .");
        run("$git remote set-url origin $repository", ['env' => $env]);
        run("$git checkout --force $target");
    } else {
        throw new ConfigurationException(parse("Unknown `update_code_strategy` option: {{update_code_strategy}}."));
    }

    // Save git revision in REVISION file.
    $rev = escapeshellarg(run("$git rev-list $target -1"));
    run("echo $rev > {{release_path}}/REVISION");
});
