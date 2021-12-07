<?php
/*
## Installing

Add to your _deploy.php_

```php
require 'contrib/git.php';
```

## Configuration
- `git_remote`, Set the remote to push the new tag to.  Defaults to origin
- `git_local`, Set the tag to be pushed from localhost.  Default to true

## Usage

Set a git on the host and release number to a git repository alias_##

```php
after('deploy:publish', 'git:tag');
```

 */

namespace Deployer;

function gitCurrentCommit(): string
{
    $git = whichLocal('git');

    $branch = currentHost()->get('branch');
    // TODO - run on remote server to get the commit id
    $command = "$git fetch && git rev-parse --short $branch";
    $commit = runLocally($command);
    return $commit;
}

function gitReleaseCurrent(): int
{
    $release = basename(currentHost()->get('release_name'));
    return (empty($release) || !is_numeric($release)) ? 1 : (int) $release;
}

// function gitReleaseNext(): int
// {
//     $release = gitReleaseCurrent();
//     return (empty($release)) ? 1 : $release + 1;
// }

task('git:log', function () {
    $cd = which('cd');
    $git = which('git');
    $head = which('head');
    $deployPath = currentHost()->get('deploy_path');
    $command = "$cd \"$deployPath/current\" && $git log --pretty=oneline | $head -n 5";
    $log = run($command);
    writeln($log);
})->desc('Get the git commit hash of the currently deployed codebase');

task('git:tag', function () {
    $git = whichLocal('git');

    $alias = currentHost()->getAlias();
    $remote = get('git_remote', 'origin');

    $release = gitReleaseCurrent();
    $commit = gitCurrentCommit();

    $tag = $alias . '_' . $release;
    $message = date('Y-m-d H:i:s');

    $tagCommand = "$git tag -fa $tag $commit -m '$message'";
    $pushCommand = "$git push $remote $tag";
    $fetchCommand = "$git fetch";
    $tagPushFetchCommand = "$tagCommand && $pushCommand && $fetchCommand";
    runLocally($tagPushFetchCommand);
})->desc('Tag the current deployment and push to remote git repository');
