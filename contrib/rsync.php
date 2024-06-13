<?php
/*
:::warning
This must not be confused with `/src/Utility/Rsync.php`, deployer's built-in rsync. Their configuration options are also very different, read carefully below.
:::

## Configuration options

- **rsync**: Accepts an array with following rsync options (all are optional and defaults are ok):
    - *exclude*: accepts an *array* with patterns to be excluded from sending to server
    - *exclude-file*: accepts a *string* containing absolute path to file, which contains exclude patterns
    - *include*: accepts an *array* with patterns to be included in sending to server
    - *include-file*: accepts a *string* containing absolute path to file, which contains include patterns
    - *filter*: accepts an *array* of rsync filter rules
    - *filter-file*: accepts a *string* containing merge-file filename.
    - *filter-perdir*: accepts a *string* containing merge-file filename to be scanned and merger per each directory in rsync list on files to send
    - *flags*: accepts a *string* of flags to set when calling rsync command. Please **avoid** flags that accept params, and use *options* instead.
    - *options*: accepts an *array* of options to set when calling rsync command. **DO NOT** prefix options with `--` as it's automatically added.
    - *timeout*: accepts an *int* defining timeout for rsync command to run locally.

### Sample Configuration:

Following is default configuration. By default rsync ignores only git dir and `deploy.php` file.

```php
// deploy.php

set('rsync',[
    'exclude'      => [
        '.git',
        'deploy.php',
    ],
    'exclude-file' => false,
    'include'      => [],
    'include-file' => false,
    'filter'       => [],
    'filter-file'  => false,
    'filter-perdir'=> false,
    'flags'        => 'rz', // Recursive, with compress
    'options'      => ['delete'],
    'timeout'      => 60,
]);
```

If You have multiple excludes, You can put them in file and reference that instead. If You use `deploy:rsync_warmup` You could set additional options that could speed-up and/or affect way things are working. For example:

```php
// deploy.php

set('rsync',[
    'exclude'       => ['excludes_file'],
    'exclude-file'  => '/tmp/localdeploys/excludes_file', //Use absolute path to avoid possible rsync problems
    'include'       => [],
    'include-file'  => false,
    'filter'        => [],
    'filter-file'   => false,
    'filter-perdir' => false,
    'flags'         => 'rzcE', // Recursive, with compress, check based on checksum rather than time/size, preserve Executable flag
    'options'       => ['delete', 'delete-after', 'force'], //Delete after successful transfer, delete even if deleted dir is not empty
    'timeout'       => 3600, //for those huge repos or crappy connection
]);
```


### Parameter

- **rsync_src**: per-host rsync source. This can be server, stage or whatever-dependent. By default it's set to current directory
- **rsync_dest**: per-host rsync destination. This can be server, stage or whatever-dependent. by default it's equivalent to release deploy destination.

### Sample configurations:

This is default configuration:

```php
set('rsync_src', __DIR__);
set('rsync_dest','{{release_path}}');
```

If You use local deploy recipe You can set src to local release:

```php
host('hostname')
    ->hostname('10.10.10.10')
    ->port(22)
    ->set('deploy_path','/your/remote/path/app')
    ->set('rsync_src', '/your/local/path/app')
    ->set('rsync_dest','{{release_path}}');
```

## Usage

- `rsync` task

    Set `rsync_src` to locally cloned repository and rsync to `rsync_dest`. Then set this task instead of `deploy:update_code` in Your `deploy` task if Your hosting provider does not allow git.

- `rsync:warmup` task

    If Your deploy task looks like:

    ```php
    task('deploy', [
        'deploy:prepare',
        'deploy:release',
        'rsync',
        'deploy:vendors',
        'deploy:symlink',
    ])->desc('Deploy your project');
    ```

    And Your `rsync_dest` is set to `{{release_path}}` then You could add this task to run before `rsync` task or after `deploy:release`, whatever is more convenient.

 */
namespace Deployer;

use Deployer\Component\Ssh\Client;
use Deployer\Host\Localhost;
use Deployer\Task\Context;

set('rsync', [
    'exclude' => [
        '.git',
        'deploy.php',
    ],
    'exclude-file' => false,
    'include' => [],
    'include-file' => false,
    'filter' => [],
    'filter-file' => false,
    'filter-perdir' => false,
    'flags' => 'rz',
    'options' => ['delete'],
    'timeout' => 300,
]);

set('rsync_src', __DIR__);
set('rsync_dest', '{{release_path}}');

set('rsync_excludes', function () {
    $config = get('rsync');
    $excludes = $config['exclude'];
    $excludeFile = $config['exclude-file'];
    $excludesRsync = '';
    foreach ($excludes as $exclude) {
        $excludesRsync.=' --exclude=' . escapeshellarg($exclude);
    }
    if (!empty($excludeFile) && file_exists($excludeFile) && is_file($excludeFile) && is_readable($excludeFile)) {
        $excludesRsync .= ' --exclude-from=' . escapeshellarg($excludeFile);
    }

    return $excludesRsync;
});

set('rsync_includes', function () {
    $config = get('rsync');
    $includes = $config['include'];
    $includeFile = $config['include-file'];
    $includesRsync = '';
    foreach ($includes as $include) {
        $includesRsync.=' --include=' . escapeshellarg($include);
    }
    if (!empty($includeFile) && file_exists($includeFile) && is_file($includeFile) && is_readable($includeFile)) {
        $includesRsync .= ' --include-from=' . escapeshellarg($includeFile);
    }

    return $includesRsync;
});

set('rsync_filter', function () {
    $config = get('rsync');
    $filters = $config['filter'];
    $filterFile = $config['filter-file'];
    $filterPerDir = $config['filter-perdir'];
    $filtersRsync = '';
    foreach ($filters as $filter) {
        $filtersRsync.=" --filter='$filter'";
    }
    if (!empty($filterFile)) {
        $filtersRsync .= " --filter='merge $filterFile'";
    }
    if (!empty($filterPerDir)) {
        $filtersRsync .= " --filter='dir-merge $filterPerDir'";
    }
    return $filtersRsync;
});

set('rsync_options', function () {
    $config = get('rsync');
    $options = $config['options'];
    $optionsRsync = [];
    foreach ($options as $option) {
        $optionsRsync[] = "--$option";
    }
    return implode(' ', $optionsRsync);
});


desc('Warmups remote Rsync target');
task('rsync:warmup', function() {
    $config = get('rsync');

    $source = "{{current_path}}";
    $destination = "{{deploy_path}}/release";

    if (test("[ -d $(echo $source) ]")) {
        run("rsync -{$config['flags']} {{rsync_options}}{{rsync_excludes}}{{rsync_includes}}{{rsync_filter}} $source/ $destination/");
    } else {
        writeln("<comment>No way to warmup rsync.</comment>");
    }
});


desc('Rsync local->remote');
task('rsync', function() {
    $config = get('rsync');

    $src = get('rsync_src');
    while (is_callable($src)) {
        $src = $src();
    }

    if (!trim($src)) {
        // if $src is not set here rsync is going to do a directory listing
        // exiting with code 0, since only doing a directory listing clearly
        // is not what we want to achieve we need to throw an exception
        throw new \RuntimeException('You need to specify a source path.');
    }

    $dst = get('rsync_dest');
    while (is_callable($dst)) {
        $dst = $dst();
    }

    if (!trim($dst)) {
        // if $dst is not set here we are going to sync to root
        // and even worse - depending on rsync flags and permission -
        // might end up deleting everything we have write permission to
        throw new \RuntimeException('You need to specify a destination path.');
    }

    $host = Context::get()->getHost();
    if ($host instanceof Localhost) {
        runLocally("rsync -{$config['flags']} {{rsync_options}}{{rsync_includes}}{{rsync_excludes}}{{rsync_filter}} '$src/' '$dst/'", $config);
        return;
    }

    $sshArguments = $host->connectionOptionsString();
    runLocally("rsync -{$config['flags']} -e 'ssh $sshArguments' {{rsync_options}}{{rsync_includes}}{{rsync_excludes}}{{rsync_filter}} '$src/' '{$host->connectionString()}:$dst/'", $config);
});
