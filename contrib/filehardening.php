<?php
/*
## Installing

Add to your _deploy.php_

```php
require 'contrib/filehardening.php';
```

## Configuration
- `dir_permissions`,
- `file_permissions`

## Usage

Set hardened permissions on source files and folders

```php
before('deploy:publish', 'deploy:harden');
```

 */

namespace Deployer;

function filesHarden(string $path, string $directoryPerms = 'u=rx,g=rx,o=rx', string $filePerms = 'u=r,g=r,o=r'): void
{
    $find = which('find');
    $test = which('test');
    $chmod = which('chmod');

    $dPerms = get('dir_permissions', $directoryPerms);
    $fPerms = get('file_permissions', $filePerms);

    $dcommand = "$test -d $path && $find $path -type d -exec $chmod $dPerms '{}' \;";
    run($dcommand);
    $fcommand = "$test -d $path/. && $find $path -type f -exec $chmod $fPerms '{}' \;";
    run($fcommand);
}

function filesUnhardenCommand(string $path, string $filePerms = 'u+rwx,g+rwx'): string
{
    $test = which('test');
    $chmod = which('chmod');
    $command = "$test -d $path && $chmod -R $filePerms $path";
    return $command;
}

task('deploy:harden', function () {
    filesHarden('{{release_path}}');
})->desc('Hardens site permissions');

/* ----------------- Task Overrides ----------------- */
// TODO - Change these to use before hook

// Needs to be overridden to account for hardened file/directory permissions
task('deploy:cleanup', function () {
    $releases = get('releases_list');
    $keep = get('keep_releases');
    $sudo = get('cleanup_use_sudo') ? 'sudo' : '';
    $runOpts = [];

    if ($keep === -1) {
        // Keep unlimited releases.
        return;
    }

    while ($keep > 0) {
        array_shift($releases);
        --$keep;
    }

    foreach ($releases as $release) {
        // Unharden permissions before removal
        $command = filesUnhardenCommand("{{deploy_path}}/releases/$release");
        //$command = "test -d {{deploy_path}}/releases/$release && chmod -R u+rwx {{deploy_path}}/releases/$release";
        run($command, $runOpts);

        run("$sudo rm -rf {{deploy_path}}/releases/$release", $runOpts);
    }

    run("cd {{deploy_path}} && if [ -e release ]; then rm release; fi", $runOpts);
})->desc('Cleanups old releases after unhardening files');

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

    // Unharden permissions before removal
    $command = filesUnhardenCommand("{{deploy_path}}/releases/$currentRelease");
    //$command = "test -d {{deploy_path}}/releases/{$releases[0]} && chmod -R u+rwx {{deploy_path}}/releases/{$releases[0]}";
    run($command);

    // Symlink to old release.
    run("{{bin/symlink}} releases/$candidate {{current_path}}");

    // Mark release as bad.
    $timestamp = timestamp();
    run("echo '$timestamp,{{user}}' > releases/$currentRelease/BAD_RELEASE");

    writeln("<info>rollback</info> to release <info>$candidate</info> was <success>successful</success>");
})->desc('Rollbacks to the previous release');
