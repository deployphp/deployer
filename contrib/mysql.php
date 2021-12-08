<?php
/*
## Installing

Add to your _deploy.php_

```php
require 'contrib/mysql.php';
```

## Configuration
- `mysql_dump_switches', mysqldump command line switches
- `find_replace_table_exclusions', Array of tables to skip when doing a find replace

## Host Configuration
- `mysql_domain`,
- `mysql_host`,
- `mysql_name`,
- `mysql_pass`,
- `mysql_port`,
- `mysql_user`,
- `production`, Set production flag as true on production host to prevent any destructive actions from running on this host

## Usage

Provides common mysql sync/backup tasks

```php

```

 */

namespace Deployer;

use Deployer\Host\Host;

set('mysql_dump_switches', '--max_allowed_packet=128M --single-transaction --quick --extended-insert --allow-keywords --events --routines --compress --extended-insert --create-options --add-drop-table --add-locks --no-tablespaces');
set('find_replace_table_exclusions', ['wp_usermeta', 'wp_usermeta_copy']);

class Mysql
{

    //private $host;

    /**
     * Mysql
     *
     * @param Host $commandHost This is the host from which all commands will be run
     */
    public function __construct()
    {
        // $this->host = $commandHost;
    }

    protected function whichLocally(string $name): string
    {
        $dryRun = get('dry-run', false) || input()->getOption('dry-run');
        if ($dryRun) {
            return $name;
        }

        $nameEscaped = escapeshellarg($name);

        // Try `command`, should cover all Bourne-like shells
        // Try `which`, should cover most other cases
        // Fallback to `type` command, if the rest fails
        $path = runLocally("command -v $nameEscaped || which $nameEscaped || type -p $nameEscaped");
        if (empty($path)) {
            throw new \RuntimeException("Can't locate [$nameEscaped] - neither of [command|which|type] commands are available");
        }

        // Deal with issue when `type -p` outputs something like `type -ap` in some implementations
        return trim(str_replace("$name is", "", $path));
    }

    public function isHostProduction(Host $host): bool
    {
        if ($host->getAlias() == 'production') {
            return true;
        }

        if ($host->get('production', false) === true) {
            return true;
        }

        if ($host->get('branch', null) == 'production') {
            return true;
        }

        return false;
    }

    public function hostCredentials(Host $host): object
    {
        $required = [
            'mysql_domain',
            'mysql_host',
            'mysql_name',
            'mysql_pass',
            'mysql_port',
            'mysql_user'
        ];
        $credentials = [];

        $credentials = new \stdClass();
        foreach ($required as $key) {
            if ($host->hasOwn($key) === false) {
                throw new \RuntimeException("$key is not defined");
            }
            $value = $host->get($key);

            $shortKey = str_replace('mysql_', '', $key);
            $credentials->{$shortKey} = $value;
        }
        return $credentials;
    }

    protected function hostPortUserPassword(object $creds): string
    {
        $connection = "--host=\"$creds->host\" --port=\"$creds->port\" --user=\"$creds->user\" --password=\"$creds->pass\"";
        return $connection;
    }

    public function findReplaceCommand(Host $source, Host $destination): string
    {
        $php = $this->whichLocally('php');

        $S = $this->hostCredentials($source);
        $D = $this->hostCredentials($destination);
        $destHostPortUserPassword = "--host=\"$D->host\" --port=\"$D->port\" --user=\"$D->user\" --pass=\"$D->pass\"";

        $tableExclusions = implode(',', get('find_replace_table_exclusions', ''));
        if (!empty($tableExclusions)) {
            $tableExclusions = "--exclude-tables=\"$tableExclusions\"";
        }

        $script = __DIR__ . "/../../../interconnectit/search-replace-db/srdb.cli.php";
        $command = "$php $script $tableExclusions $destHostPortUserPassword --name=\"$D->name\" --search=\"$S->domain\" --replace=\"$D->domain\"";
        return $command;
    }

    public function transferCommand(Host $source, Host $destination): string
    {
        $mysqldump = $this->whichLocally('mysqldump');
        $mysql = $this->whichLocally('mysql');

        $S = $this->hostCredentials($source);
        $sourceHostPortUserPassword = $this->hostPortUserPassword($S);

        $D = $this->hostCredentials($destination);
        $destHostPortUserPassword = $this->hostPortUserPassword($D);

        $dumpSwitches = get('mysql_dump_switches');
        $pullCommand = "$mysqldump $dumpSwitches $sourceHostPortUserPassword $S->name | $mysql $destHostPortUserPassword $D->name";
        return $pullCommand;
    }

    public function backupCommand(Host $source): string
    {
        $mysqldump = $this->whichLocally('mysqldump');
        $gzip = $this->whichLocally('gzip');

        $H = $this->hostCredentials($source);
        $hostPortUserPassword = $this->hostPortUserPassword($H);

        $dumpName = sprintf('db-%s-%s-%s.sql', $source->getAlias(), $H->name, date('YmdHis'));
        $dumpSwitches = get('mysql_dump_switches');
        $command = "$mysqldump $dumpSwitches $hostPortUserPassword $H->name > \"$dumpName\" && $gzip \"$dumpName\"";
        return $command;
    }


    /**
     * Backup a remote database to the local filesystem
     *
     * @return void
     */
    public function backup(Host $source): void
    {
        $pullCommand = $this->backupCommand($source);
        runLocally($pullCommand);
    }

    /**
     * Truncate all tables in the host database
     *
     * @param Host $host
     * @return void
     */
    public function clear(Host $host): void
    {
        if ($this->isHostProduction($host)) {
            throw new \RuntimeException("Command cannot be run on production");
        }

        $mysql = $this->whichLocally('mysql');

        $H = $this->hostCredentials($host);
        $hostPortUserPassword = $this->hostPortUserPassword($H);

        $connection = "$mysql $hostPortUserPassword $H->name";

        $tablesCommand = $connection . " -e 'SHOW TABLES' ";

        $dryRun = get('dry-run', false);
        if ($dryRun) {
            set('dry-run', false);
        }
        $tables = runLocally($tablesCommand);
        if ($dryRun) {
            set('dry-run', true);
        }

        $tableArray = explode(PHP_EOL, $tables);
        unset($tableArray[0]); // removes 'Tables in dbname' entry
        if (empty($tableArray)) {
            return;
        }

        foreach ($tableArray as $table) {
            runLocally($connection . " -e 'DROP TABLE `$table`'");
        }
    }

    /**
     * Pull a remote mysql database to localhost
     *
     * @param Host $host
     * @return void
     */
    public function pull(Host $source, Host $destination): void
    {
        $this->clear($destination);
        $pullCommand = $this->transferCommand($source, $destination);
        runLocally($pullCommand, ['real_time_output' => true]);
    }

    /**
     * Find a replace name of the source domain with the destination domain in a mysql database
     *
     * @param Host $source
     * @param Host $destination
     * @return void
     */
    public function findReplace(Host $source, Host $destination): void
    {
        $replaceCommand = $this->findReplaceCommand($source, $destination);
        runLocally($replaceCommand, ['real_time_output' => true]);
    }

    /**
     * Pull a remote db and run domain name replacements
     *
     * @param Host $source
     * @param Host $destination
     * @return void
     */
    public function pullReplace(Host $source, Host $destination): void
    {
        $this->pull($source, $destination);
        $this->findReplace($source, $destination);
    }
}

// Tasks

task('db:backup', function () {
    $mysql = new Mysql();
    $mysql->backup(currentHost());
})->desc('Pull db from a remote host to localhost using mysqldump');

task('db:clear', function () {
    $mysql = new Mysql();
    $mysql->clear(currentHost());
})->desc('Clear all tables from localhost database');

task('db:pull', function () {
    $mysql = new Mysql();
    $mysql->pull(currentHost(), hostLocalhost());
})->desc('Pull db from a remote host to localhost using mysqldump');

task('db:replace', function () {
    $mysql = new Mysql();
    $mysql->findReplace(currentHost(), hostLocalhost());
})->desc('Replace the host domain with the localhost domain in the local database');

task('db:pull-replace', function () {
    $mysql = new Mysql();
    $mysql->pull(currentHost(), hostLocalhost());
    $mysql->findReplace(currentHost(), hostLocalhost());
})->desc('Pull db from a remote host to localhost using mysqldump and replace the host domain with the localhost domain in the local database');
