<?php
/*
## Installing

Add to your _deploy.php_

```php
require 'contrib/staging.php';
```

## Configuration
- ``, None yet


## Usage

Sets up a host as staging host that can pull files/db from production

```php

```

 */

namespace Deployer;

use Deployer\Host\Host;
use Deployer\Host\Localhost;

require_once __DIR__ . '/mysql.php';

function hostFromStage(string $stage): Host
{
    $hosts = Deployer::get()->hosts;
    foreach ($hosts as $host) {
        $hostStage = $host->get('stage');
        if (trim(strtolower($stage)) == trim(strtolower($hostStage))) {
            return $host;
        }
    }
    throw new \RuntimeException("$stage stage is not defined");
}

function hostsAreSame(Host $host1, Host $host2): bool
{
    $host1Uri = $host1->getRemoteUser() . '@' . $host1->getHostname();
    $host2Uri = $host2->getRemoteUser() . '@' . $host2->getHostname();
    if (trim(strtolower($host1Uri)) == trim(strtolower($host2Uri))) {
        return true;
    }
    return false;
}

function getSharedWritable(): array
{
    $writable = get('writable_dirs', []);
    $shared = get('shared_dirs', []);
    $sharedWritable = array_intersect($shared, $writable);
    return $sharedWritable;
}

function getRsyncCommand(Host $source, string $source_path, Host $destination, string $destination_path, bool $local = true): string
{
    $rsync = whichContextual('rsync', $local);
    $switches = '-rlztv --progress --size-only --ipv4 --delete';  # --delete-after? --delete-before?

    $rsync_excludes = []; // todo add global variable
    $excludes = '';
    if (!empty($rsync_excludes)) {
        foreach ($rsync_excludes as $exclude) {
            $excludes .= " --exclude \"$exclude\"";
        }
    }

    // source
    $sourceUri = $source->getRemoteUser() . '@' . $source->getHostname() . ':' . parse($source_path);
    $port = '';
    if ($source instanceof Localhost || hostsAreSame($source, $destination)) {
        $sourceUri = parse($source_path);
    } else {
        $port = "-e \"ssh -p " . $source->getPort() . "\"";
        $sourceUri = $source->getRemoteUser() . '@' . $source->getHostname() . ':' . parse($source_path);
    }

    // destination
    if ($destination instanceof Localhost || hostsAreSame($source, $destination)) {
        $destinationUri = parse($destination_path);
    } else {
        $destinationUri = $destination->getRemoteUser() . '@' . $destination->getHostname() . ':' . parse($destination_path);
    }

    $command = "$rsync $port $switches $excludes $sourceUri $destinationUri";
    return $command;
}

function filesPullSharedWritable(Host $host): void
{
    $sharedWritable = getSharedWritable();
    if (empty($sharedWritable)) {
        writeln('<error>No shared writable directories are defined</error>');
        return;
    }

    $sourcePath = parse($host->get('deploy_path'));

    $localhost = hostFromAlias('localhost');
    $localPath = parse($localhost->get('deploy_path'));

    foreach ($sharedWritable as $dir) {
        $dir = parse($dir);
        $absSource = $sourcePath . '/current/' . $dir . '/';
        $absDestination = $localPath . '/' .  $dir . '/';
        $rsyncCommand = getRsyncCommand($host, $absSource, $localhost, $absDestination);
        runLocally($rsyncCommand);
    }
}

function stagingDbPullReplace(): void
{
    $production = hostFromStage('production');
    $staging = hostFromStage('staging');

    $pullCommand = getDbDumpCommand($production, $staging);
    $replaceCommand = getDbReplaceCommand($production, $staging);

    invoke('db:clear');
    runLocally($pullCommand);
    runLocally($replaceCommand, ['tty' => true]);
}

function stagingFilesPull(): void
{
    $sharedWritable = getSharedWritable();
    if (empty($sharedWritable)) {
        writeln('<error>No shared writable directories are defined</error>');
        return;
    }

    $source = hostFromStage('production');
    $sourcePath = parse($source->get('deploy_path'));

    $destination = hostFromStage('staging');
    $destinationPath = parse($destination->get('deploy_path'));

    foreach ($sharedWritable as $dir) {
        $dir = parse($dir);
        $absSource = $sourcePath . '/current/' . $dir . '/';
        $absDestination = $destinationPath . '/current/' .  $dir . '/';
        $rsyncCommand = getRsyncCommand($source, $absSource, $destination, $absDestination, false);
        run($rsyncCommand);
    }
}

task('files:pull', function () {
    filesPullSharedWritable(currentHost());
})->desc('Downloads shared writable folders to the localhost');

task('pull-all', [
    'files:pull',
    'db:pull-replace',
])->desc('Pull db from a remote stage, replaces instances of domain in db, and pulls writable files');

task('staging:db:pull-replace', function () {
    stagingDbPullReplace();
})->select('stage=staging')->desc('Truncate staging db, pull db from a production, find/replace production with staging domain');

task('staging:files:pull', function () {
    stagingFilesPull();
})->desc('Remove writable staging directories, copy writable directories from production to staging');

task('staging:pull-all', function () {
    invoke('staging:files:pull');
    invoke('staging:db:pull-replace');
})->select('stage=staging')->desc('Copy writable directories from production to staging and truncate staging db, pull db from a production, find/replace production with staging domain');
