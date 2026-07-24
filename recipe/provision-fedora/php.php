<?php

namespace Deployer;

desc('Installs PHP packages');
task('provision:php', function () {
    set('remote_user', get('provision_user'));

    // curl, opcache and sqlite3/pdo_sqlite ship inside php-common/php-pdo on
    // Fedora already; there is no php-imap (dropped upstream) and zip is
    // packaged as php-pecl-zip instead of php-zip. The generic `php`
    // meta-package is skipped on purpose: it Recommends httpd, pulling in a
    // second webserver we never use since Caddy is already handling that.
    $packages = [
        'php-bcmath',
        'php-cli',
        'php-common',
        'php-fpm',
        'php-gd',
        'php-intl',
        'php-mbstring',
        'php-mysqlnd',
        'php-pdo',
        'php-pecl-memcached',
        'php-pecl-redis6',
        'php-pecl-zip',
        'php-pgsql',
        'php-soap',
        'php-xml',
    ];
    run('dnf install -y ' . implode(' ', $packages), timeout: 900);

    $version = run("php -r 'echo PHP_MAJOR_VERSION . \".\" . PHP_MINOR_VERSION;'");
    info("Installed PHP $version (from Fedora's repositories)");

    // Configure PHP (cli and fpm share the same php.ini on Fedora)
    run("sed -i 's/error_reporting = .*/error_reporting = E_ALL/' /etc/php.ini");
    run("sed -i 's/display_errors = .*/display_errors = On/' /etc/php.ini");
    run("sed -i 's/memory_limit = .*/memory_limit = 512M/' /etc/php.ini");
    run("sed -i 's/upload_max_filesize = .*/upload_max_filesize = 128M/' /etc/php.ini");
    run("sed -i 's/;date.timezone.*/date.timezone = UTC/' /etc/php.ini");
    run("sed -i 's/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/' /etc/php.ini");

    // Configure FPM Pool to run as the `caddy` user, so Caddy (our only
    // webserver) can reach the socket without needing a shared `apache` group.
    run("sed -i 's/^user = .*/user = caddy/' /etc/php-fpm.d/www.conf");
    run("sed -i 's/^group = .*/group = caddy/' /etc/php-fpm.d/www.conf");
    run("sed -i 's|^listen = .*|listen = /run/php-fpm/www.sock|' /etc/php-fpm.d/www.conf");
    run("sed -i 's/^;listen.owner = .*/listen.owner = caddy/' /etc/php-fpm.d/www.conf");
    run("sed -i 's/^;listen.group = .*/listen.group = caddy/' /etc/php-fpm.d/www.conf");
    // Fedora's default pool sets an active listen.acl_users (apache,nginx),
    // which per its own docs makes FPM use POSIX ACLs and silently ignore
    // listen.owner/listen.group above -- without this, Caddy can't reach the socket.
    run("sed -i 's/^listen.acl_users = .*/listen.acl_users = caddy/' /etc/php-fpm.d/www.conf");
    run("sed -i 's/;request_terminate_timeout = .*/request_terminate_timeout = 60/' /etc/php-fpm.d/www.conf");
    run("sed -i 's/;catch_workers_output = .*/catch_workers_output = yes/' /etc/php-fpm.d/www.conf");
    run("sed -i 's/;php_flag\[display_errors\] = .*/php_flag[display_errors] = yes/' /etc/php-fpm.d/www.conf");
    run("sed -i 's/;php_admin_value\[error_log\] = .*/php_admin_value[error_log] = \/var\/log\/php-fpm\/www-error.log/' /etc/php-fpm.d/www.conf");
    run("sed -i 's/;php_admin_flag\[log_errors\] = .*/php_admin_flag[log_errors] = on/' /etc/php-fpm.d/www.conf");

    // Configure PHP sessions directory
    run('mkdir -p /var/lib/php/session');
    run('chmod 733 /var/lib/php/session');
    run('chmod +t /var/lib/php/session');

    run('systemctl enable --now php-fpm');
    run('systemctl restart php-fpm');
})
    ->verbose()
    ->limit(1);

desc('Shows php-fpm logs');
task('logs:php-fpm', function () {
    run('sudo tail -f /var/log/php-fpm/www-error.log');
})->verbose();

desc('Installs Composer');
task('provision:composer', function () {
    run('curl -sS https://getcomposer.org/installer | php');
    run('mv composer.phar /usr/local/bin/composer');
})->oncePerNode();
