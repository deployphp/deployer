<?php

namespace Deployer;

set('db_type', function () {
    $supportedDbTypes = [
        'none',
        'mariadb',
        'postgresql',
    ];
    return askChoice(' What DB to install? ', $supportedDbTypes, 0);
});

set('db_name', function () {
    return ask(' DB name: ', 'prod');
});

set('db_user', function () {
    return ask(' DB user: ', 'deployer');
});

set('db_password', function () {
    return askHiddenResponse(' DB password: ');
});

// PGDG ships a much more complete/current set of PostgreSQL major versions
// than Fedora's own repositories, so provision:postgresql relies on it.
set('postgresql_version', function () {
    return ask(' What PostgreSQL version to install? ', '17', ['13', '14', '15', '16', '17', '18']);
});

desc('Provision databases');
task('provision:databases', function () {
    set('remote_user', get('provision_user'));

    $dbType = get('db_type');
    if ($dbType === 'none') {
        return;
    }
    invoke('provision:' . $dbType);
})
    ->limit(1);

desc('Provision MariaDB');
task('provision:mariadb', function () {
    run('dnf install -y mariadb-server', timeout: 900);
    run('systemctl enable --now mariadb');
    run("mysql --user=\"root\" -e \"CREATE USER IF NOT EXISTS '{{db_user}}'@'0.0.0.0' IDENTIFIED BY '%db_password%';\"", secrets: ['db_password' => get('db_password')]);
    run("mysql --user=\"root\" -e \"CREATE USER IF NOT EXISTS '{{db_user}}'@'%' IDENTIFIED BY '%db_password%';\"", secrets: ['db_password' => get('db_password')]);
    run("mysql --user=\"root\" -e \"GRANT ALL PRIVILEGES ON *.* TO '{{db_user}}'@'0.0.0.0' WITH GRANT OPTION;\"");
    run("mysql --user=\"root\" -e \"GRANT ALL PRIVILEGES ON *.* TO '{{db_user}}'@'%' WITH GRANT OPTION;\"");
    run("mysql --user=\"root\" -e \"FLUSH PRIVILEGES;\"");
    run("mysql --user=\"root\" -e \"CREATE DATABASE IF NOT EXISTS {{db_name}} character set UTF8mb4 collate utf8mb4_bin;\"");
});

desc('Provision PostgreSQL');
task('provision:postgresql', function () {
    $version = get('postgresql_version');

    // Fedora's own repos only ship a single PostgreSQL version. PGDG gives us
    // a properly maintained, versioned package instead.
    run('dnf install -y https://download.postgresql.org/pub/repos/yum/reporpms/F-{{fedora_version}}-x86_64/pgdg-fedora-repo-latest.noarch.rpm');
    run('dnf -y -q module disable postgresql', nothrow: true);

    run("dnf install -y postgresql$version-server postgresql$version-contrib", timeout: 900);
    run("/usr/pgsql-$version/bin/postgresql-$version-setup initdb", nothrow: true);
    run("systemctl enable --now postgresql-$version");

    run("sudo -u postgres /usr/pgsql-$version/bin/psql <<< $'CREATE DATABASE {{db_name}};'");
    run("sudo -u postgres /usr/pgsql-$version/bin/psql <<< $'CREATE USER {{db_user}} WITH ENCRYPTED PASSWORD \'%db_password%\';'", secrets: ['db_password' => get('db_password')]);
    run("sudo -u postgres /usr/pgsql-$version/bin/psql <<< $'GRANT ALL PRIVILEGES ON DATABASE {{db_name}} TO {{db_user}};'");
});
