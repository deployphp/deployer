<?php
/*
## Installing

Add to your _deploy.php_

```php
require 'contrib/staging.php';
```

## Configuration
- `staging_rsync_switches`, Rsync switches used when syncing shared writable files
- `staging_rsync_excludes`, Array of folders to --exclude when rsyncing shared writable folders

## Usage

Sets up a host as staging host that can pull files/db from production

```php

```

 */

namespace Deployer;

use Deployer\Host\Host;
use Deployer\Host\Localhost;

require_once __DIR__ . '/mysql.php';
require_once __DIR__ . '/filetransfer.php';

// add checks for all variables needed

// set('staging_rsync_switches', '-rlztv --progress --size-only --ipv4 --delete');
// set('staging_rsync_excludes', []);

// class Staging
// {

//     function hostFromStage(string $stage): Host
//     {
//         $hosts = Deployer::get()->hosts;
//         foreach ($hosts as $host) {
//             $hostStage = $host->get('stage');
//             if (trim(strtolower($stage)) == trim(strtolower($hostStage))) {
//                 return $host;
//             }
//         }
//         throw new \RuntimeException("$stage stage is not defined");
//     }

//     // function stagingFilesPull(Host $host): void
//     // {
//     //     $sharedWritable = $this->getSharedWritable();
//     //     if (empty($sharedWritable)) {
//     //         writeln('<error>No shared writable directories are defined</error>');
//     //         return;
//     //     }

//     //     $source = $host;
//     //     $sourcePath = parse($source->getDeployPath());

//     //     $destination = hostFromStage('staging');
//     //     $destinationPath = parse($destination->getDeployPath());

//     //     foreach ($sharedWritable as $dir) {
//     //         $dir = parse($dir);
//     //         $absSource = $sourcePath . '/current/' . $dir . '/';
//     //         $absDestination = $destinationPath . '/current/' .  $dir . '/';
//     //         $rsyncCommand = $this->rsyncCommand($source, $absSource, $destination, $absDestination, false);
//     //         run($rsyncCommand);
//     //     }
//     // }
// }

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

task('staging:files:pull', function () {
    $files = new FileTransfer();
    $files->pullSharedWritable(currentHost(), hostFromStage('staging'));
})->desc('Remove writable staging directories, copy writable directories from production to staging');


task('staging:db:pull-replace', function () {
    $mysql = new Mysql();
    $mysql->pullReplace(currentHost(), hostFromStage('staging'));
})->desc('Truncate staging db, pull db from a production, find/replace production with staging domain');

task('staging:pull-all', [
    'staging:files:pull',
    'staging:db:pull-replace'
])->desc('Copy writable directories from production to staging and truncate staging db, pull db from a production, find/replace production with staging domain');
