<?php
/*
## Installing

Add to your _deploy.php_

```php
require 'contrib/filetransfer.php';
```

## Configuration
- ``,
- ``

## Usage

Transfer files via rsync between server instances

```php

```

 */

namespace Deployer;

use Deployer\Host\Host;
use Deployer\Host\Localhost;

class FileTransfer
{

    public function rsyncCommand(Host $source, string $source_path, Host $destination, string $destination_path, bool $local = true): string
    {
        $rsync = whichContextual('rsync', $local);
        $switches = get('staging_rsync_switches', '-rlztv --progress --size-only --ipv4 --delete');  # --delete-after? --delete-before?

        $rsync_excludes = get('staging_rsync_excludes', []);
        $excludes = '';
        if (!empty($rsync_excludes)) {
            foreach ($rsync_excludes as $exclude) {
                $excludes .= " --exclude \"$exclude\"";
            }
        }

        // source
        $sourceUri = $source->getRemoteUser() . '@' . $source->getHostname() . ':' . parse($source_path);
        $port = '';
        if ($source instanceof Localhost || hostsOnSameServer($source, $destination)) {
            $sourceUri = parse($source_path);
        } else {
            $port = "-e \"ssh -p " . $source->getPort() . "\"";
            $sourceUri = $source->getRemoteUser() . '@' . $source->getHostname() . ':' . parse($source_path);
        }

        // destination
        if ($destination instanceof Localhost || hostsOnSameServer($source, $destination)) {
            $destinationUri = parse($destination_path);
        } else {
            $destinationUri = $destination->getRemoteUser() . '@' . $destination->getHostname() . ':' . parse($destination_path);
        }

        $command = "$rsync $port $switches $excludes $sourceUri $destinationUri";
        return $command;
    }

    public function pullSharedWritable(Host $source, Host $destination): void
    {
        if (hostsAreSame($source, $destination)) {
            throw error("Hosts source and destination cannot be the same when pulling files");
        }

        $writable = get('writable_dirs', []);
        $shared = get('shared_dirs', []);
        $sharedWritable = array_intersect($shared, $writable);

        if (empty($sharedWritable)) {
            writeln('<error>No shared writable directories are defined</error>');
            return;
        }

        $sourcePath = parse($source->getDeployPath());
        $localPath = parse($destination->getDeployPath());

        foreach ($sharedWritable as $dir) {
            $dir = parse($dir);
            $absSource = $sourcePath . '/current/' . $dir . '/';
            $absDestination = $localPath . '/' .  $dir . '/';
            $rsyncCommand = $this->rsyncCommand($source, $absSource, $destination, $absDestination);
            runLocally($rsyncCommand);
        }
    }
}

task('files:pull', function () {
    $server = new FileTransfer();
    $server->pullSharedWritable(currentHost(), hostLocalhost());
})->desc('Downloads shared writable folders to the localhost');
