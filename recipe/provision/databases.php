<?php
namespace Deployer;

set('db_type', function () {
    $supportedDbTypes = [
        'none',
        'mysql',
        'mariadb',
        'postgresql',
    ];
    return askChoice(' What DB to install? ', $supportedDbTypes, 0);
});

set('db_name', function () {
    return ask(' DB name: ');
});

set('db_user', function () {
    return ask(' DB user: ', 'deployer');
});

set('db_password', function () {
    return askHiddenResponse(' DB password: ');
});

desc('Provision databases');
task('provision:databases', function () {
    $dbType = get('db_type');
    if ($dbType === 'none') {
        return;
    }
    invoke('provision:' . $dbType);
})
    ->limit(1);

desc('Provision MySQL');
task('provision:mysql', function () {
    run('apt-get install -y mysql-server', ['env' => ['DEBIAN_FRONTEND' => 'noninteractive'], 'timeout' => 900]);
    run("mysql --user=\"root\" -e \"CREATE USER IF NOT EXISTS '{{db_user}}'@'0.0.0.0' IDENTIFIED BY '%secret%';\"", ['secret' => get('db_password')]);
    run("mysql --user=\"root\" -e \"CREATE USER IF NOT EXISTS '{{db_user}}'@'%' IDENTIFIED BY '%secret%';\"", ['secret' => get('db_password')]);
    run("mysql --user=\"root\" -e \"GRANT ALL PRIVILEGES ON *.* TO '{{db_user}}'@'0.0.0.0' WITH GRANT OPTION;\"");
    run("mysql --user=\"root\" -e \"GRANT ALL PRIVILEGES ON *.* TO '{{db_user}}'@'%' WITH GRANT OPTION;\"");
    run("mysql --user=\"root\" -e \"FLUSH PRIVILEGES;\"");
    run("mysql --user=\"root\" -e \"CREATE DATABASE IF NOT EXISTS {{db_name}} character set UTF8mb4 collate utf8mb4_bin;\"");
});

desc('Provision MariaDB');
task('provision:mariadb', function () {
    run('apt-get install -y mariadb-server', ['env' => ['DEBIAN_FRONTEND' => 'noninteractive'], 'timeout' => 900]);
    run("mysql --user=\"root\" -e \"CREATE USER IF NOT EXISTS '{{db_user}}'@'0.0.0.0' IDENTIFIED BY '%secret%';\"", ['secret' => get('db_password')]);
    run("mysql --user=\"root\" -e \"CREATE USER IF NOT EXISTS '{{db_user}}'@'%' IDENTIFIED BY '%secret%';\"", ['secret' => get('db_password')]);
    run("mysql --user=\"root\" -e \"GRANT ALL PRIVILEGES ON *.* TO '{{db_user}}'@'0.0.0.0' WITH GRANT OPTION;\"");
    run("mysql --user=\"root\" -e \"GRANT ALL PRIVILEGES ON *.* TO '{{db_user}}'@'%' WITH GRANT OPTION;\"");
    run("mysql --user=\"root\" -e \"FLUSH PRIVILEGES;\"");
    run("mysql --user=\"root\" -e \"CREATE DATABASE IF NOT EXISTS {{db_name}} character set UTF8mb4 collate utf8mb4_bin;\"");
});

desc('Provision PostgreSQL');
task('provision:postgresql', function () {
    run('apt-get install -y postgresql postgresql-contrib', ['env' => ['DEBIAN_FRONTEND' => 'noninteractive'], 'timeout' => 900]);
    run("sudo -u postgres psql <<< $'CREATE DATABASE {{db_name}};'");
    run("sudo -u postgres psql <<< $'CREATE USER {{db_user}} WITH ENCRYPTED PASSWORD \'%secret%\';'", ['secret' => get('db_password')]);
    run("sudo -u postgres psql <<< $'GRANT ALL PRIVILEGES ON DATABASE {{db_name}} TO {{db_user}};'");
});
