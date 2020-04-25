<?php
/* (c) HAKGER[hakger.pl] Hubert Kowalski <h.kowalski@hakger.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @contributor Steve Mueller <steve.mueller@check24.de>, Niklas Vosskoetter <niklas.vosskoetter@check24.de>
 */

namespace Deployer;

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


desc('Warmup remote Rsync target');
task('rsync:warmup', function() {
    $config = get('rsync');

    $source = "{{deploy_path}}/current";
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

    $server = \Deployer\Task\Context::get()->getHost();
    if ($server instanceof \Deployer\Host\Localhost) {
        runLocally("rsync -{$config['flags']} {{rsync_options}}{{rsync_includes}}{{rsync_excludes}}{{rsync_filter}} '$src/' '$dst/'", $config);
        return;
    }

    $host = $server->getHostname();
    $port = $server->getPort() ? ' -p' . $server->getPort() : '';
    $sshArguments = $server->getSshArguments();
    $user = !$server->getRemoteUser() ? '' : $server->getRemoteUser() . '@';

    runLocally("rsync -{$config['flags']} -e 'ssh$port $sshArguments' {{rsync_options}}{{rsync_includes}}{{rsync_excludes}}{{rsync_filter}} '$src/' '$user$host:$dst/'", $config);
});
