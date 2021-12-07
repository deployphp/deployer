<?php
/*
## Installing

Add to your _deploy.php_

```php
require 'contrib/mysql.php';
```

## Configuration
- `db_domain`,
- `db_host`,
- `db_name`,
- `db_pass`,
- `db_port`,
- `db_user`

## Usage

Provides common mysql sync/backup tasks

```php

```

 */

namespace Deployer;

use Deployer\Host\Host;

function hostIsProduction(Host $host): bool
{
    if ($host->getAlias() == 'production') {
        return true;
    }

    if ($host->get('production') === true) {
        return true;
    }

    $labels = $host->getLabels();
    if (isset($labels['production'])) {
        $production = $labels['production'];
        if ($production == true || $production == 1 || $production == '1') {
            return true;
        }
    }
    if (isset($labels['stage']) && $labels['stage'] == 'production') {
        return true;
    }

    if ($host->get('branch') == 'production') {
        return true;
    }

    return false;
}

function getDbCredentials(Host $host, string $prefix = ''): array
{
    $required = [
        'db_domain',
        'db_host',
        'db_name',
        'db_pass',
        'db_port',
        'db_user'
    ];
    $credentials = [];
    foreach ($required as $key) {
        $value = $host->get($key);
        if (empty($value)) {
            throw new \RuntimeException("$key is not defined");
        }
        $credentials[$prefix . $key] = $host->get($key);
    }
    return $credentials;
}

function getDbReplaceCommand(Host $source, Host $destination, bool $local = true): string
{
    $php = whichContextual('php', $local);

    $sourceCredentials = getDbCredentials($source, 'source_');
    extract($sourceCredentials);

    $destinationCredentials = getDbCredentials($destination, 'destination_');
    extract($destinationCredentials);

    $replaceCommand = "$php " . __DIR__ . "/vendor/interconnectit/search-replace-db/srdb.cli.php -w wp_usermeta,wp_usermeta_copy -h $destination_db_host -n $destination_db_name -u $destination_db_user -p $destination_db_pass -s \"$source_db_domain\" -r \"$destination_db_domain\"";
    return $replaceCommand;
}

function getDbPullCommand(Host $source, Host $destination): string
{
    $mysqldump = which('mysqldump');
    $mysql = which('mysql');

    $sourceCredentials = getDbCredentials($source, 'source_');
    extract($sourceCredentials);

    $destinationCredentials = getDbCredentials($destination, 'destination_');
    extract($destinationCredentials);

    $dump_switches = getMysqlDumpSwitches();
    $pullCommand = "$mysqldump --port=$source_db_port --host=$source_db_host --user=$source_db_user --password=$source_db_pass $dump_switches $source_db_name | $mysql --host=$destination_db_host --port=$destination_db_port --user=$destination_db_user --password=$destination_db_pass $destination_db_name";
    return $pullCommand;
}

function getMysqlDumpSwitches(): string
{
    // defaults
    $dumpSwitches = "--max_allowed_packet=128M --single-transaction --quick --extended-insert --allow-keywords --events --routines ";
    $dumpSwitches = "${dumpSwitches} --compress --extended-insert --create-options --add-drop-table --add-locks --no-tablespaces";

    // TODO check global config

    // TODO check host config

    return $dumpSwitches;
}

function getDbDumpCommand(Host $source, Host $destination, bool $local = true): string
{
    $mysqldump = whichContextual('mysqldump', $local);
    $mysql = whichContextual('mysql', $local);

    $sourceCredentials = getDbCredentials($source, 'source_');
    extract($sourceCredentials);

    $destinationCredentials = getDbCredentials($destination, 'destination_');
    extract($destinationCredentials);

    $dump_switches = getMysqlDumpSwitches();
    $pullCommand = "$mysqldump --port=$source_db_port --host=$source_db_host --user=$source_db_user --password=$source_db_pass $dump_switches $source_db_name | $mysql --host=$destination_db_host --port=$destination_db_port --user=$destination_db_user --password=$destination_db_pass $destination_db_name";
    return $pullCommand;
}

function dbBackup(Host $host): void
{
    $mysqldump = whichLocal('mysqldump');
    $gzip = whichLocal('gzip');

    $alias = $host->getAlias();
    $sourceCredentials = getDbCredentials($host, 'source_');
    extract($sourceCredentials);

    $dumpName = sprintf('db-%s-%s-%s.sql', $alias, $source_db_name, date('YmdHis'));
    $dump_switches = getMysqlDumpSwitches();
    $pullCommand = "$mysqldump --port=$source_db_port --host=$source_db_host --user=$source_db_user --password=$source_db_pass $dump_switches $source_db_name > \"$dumpName\" && $gzip \"$dumpName\"";
    runLocally($pullCommand);
}

function dbClear(Host $host): void
{
    if (hostIsProduction($host)) {
        throw new \RuntimeException("Command cannot be run on production");
    }

    $destinationCredentials = getDbCredentials($host, 'destination_');
    extract($destinationCredentials);

    $mysql = whichLocal('mysql');
    $command = "$mysql --host=$destination_db_host --port=$destination_db_port --user=$destination_db_user --password=$destination_db_pass $destination_db_name";

    $tablesCommand = $command . " -e 'SHOW TABLES' ";
    $tables = runLocally($tablesCommand);

    $tableArray = explode(PHP_EOL, $tables);
    unset($tableArray[0]);
    if (empty($tableArray)) {
        return;
    }

    foreach ($tableArray as $table) {
        runLocally($command . " -e 'DROP TABLE `$table`'");
    }
}

function dbPull(Host $host): void
{
    dbClear($host);
    $pullCommand = getDbPullCommand($host, hostFromAlias('localhost'));
    runLocally($pullCommand, ['tty' => true]);
}

function dbFindReplace(Host $source, Host $destination): void
{
    $replaceCommand = getDbReplaceCommand($source, $destination);
    runLocally($replaceCommand, ['tty' => true]);
}

// Tasks

task('db:backup', function () {
    dbBackup(currentHost());
})->desc('Pull db from a remote host to localhost using mysqldump');

task('db:clear', function () {
    dbClear(currentHost());
})->desc('Clear all tables from localhost database');

task('db:pull', function () {
    dbPull(currentHost());
})->desc('Pull db from a remote host to localhost using mysqldump');

task('db:replace', function () {
    dbFindReplace(currentHost(), hostLocalhost());
})->desc('Replace the host domain with the localhost domain in the local database');


task('db:pull-replace', [
    'db:pull',
    'db:replace'
])->desc('Pull db from a remote host to localhost using mysqldump and replace the host domain with the localhost domain in the local database');
