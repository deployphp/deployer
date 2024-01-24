<?php
namespace Deployer;

set('php_version', function () {
    return ask(' What PHP version to install? ', '8.2', ['5.6', '7.4', '8.0', '8.1', '8.2']);
});

desc('Installs PHP packages');
task('provision:php', function () {
    $version = get('php_version');
    info("Installing PHP $version");
    $packages = [
        "php$version-bcmath",
        "php$version-cli",
        "php$version-curl",
        "php$version-dev",
        "php$version-fpm",
        "php$version-gd",
        "php$version-imap",
        "php$version-intl",
        "php$version-mbstring",
        "php$version-mysql",
        "php$version-pgsql",
        "php$version-readline",
        "php$version-soap",
        "php$version-sqlite3",
        "php$version-xml",
        "php$version-zip",
    ];
    run('apt-get install -y ' . implode(' ', $packages), ['env' => ['DEBIAN_FRONTEND' => 'noninteractive']]);

    // Configure PHP-CLI
    run("sudo sed -i 's/error_reporting = .*/error_reporting = E_ALL/' /etc/php/$version/cli/php.ini");
    run("sudo sed -i 's/display_errors = .*/display_errors = On/' /etc/php/$version/cli/php.ini");
    run("sudo sed -i 's/memory_limit = .*/memory_limit = 512M/' /etc/php/$version/cli/php.ini");
    run("sudo sed -i 's/upload_max_filesize = .*/upload_max_filesize = 128M/' /etc/php/$version/cli/php.ini");
    run("sudo sed -i 's/;date.timezone.*/date.timezone = UTC/' /etc/php/$version/cli/php.ini");

    // Configure PHP-FPM
    run("sed -i 's/error_reporting = .*/error_reporting = E_ALL/' /etc/php/$version/fpm/php.ini");
    run("sed -i 's/display_errors = .*/display_errors = On/' /etc/php/$version/fpm/php.ini");
    run("sed -i 's/memory_limit = .*/memory_limit = 512M/' /etc/php/$version/fpm/php.ini");
    run("sed -i 's/upload_max_filesize = .*/upload_max_filesize = 128M/' /etc/php/$version/fpm/php.ini");
    run("sed -i 's/;date.timezone.*/date.timezone = UTC/' /etc/php/$version/fpm/php.ini");
    run("sed -i 's/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/' /etc/php/$version/fpm/php.ini");

    // Configure FPM Pool
    run("sed -i 's/;request_terminate_timeout = .*/request_terminate_timeout = 60/' /etc/php/$version/fpm/pool.d/www.conf");
    run("sed -i 's/;catch_workers_output = .*/catch_workers_output = yes/' /etc/php/$version/fpm/pool.d/www.conf");
    run("sed -i 's/;php_flag\[display_errors\] = .*/php_flag[display_errors] = yes/' /etc/php/$version/fpm/pool.d/www.conf");
    run("sed -i 's/;php_admin_value\[error_log\] = .*/php_admin_value[error_log] = \/var\/log\/fpm-php.www.log/' /etc/php/$version/fpm/pool.d/www.conf");
    run("sed -i 's/;php_admin_flag\[log_errors\] = .*/php_admin_flag[log_errors] = on/' /etc/php/$version/fpm/pool.d/www.conf");

    // Configure PHP sessions directory
    run('chmod 733 /var/lib/php/sessions');
    run('chmod +t /var/lib/php/sessions');
})
    ->verbose()
    ->limit(1);

desc('Shows php-fpm logs');
task('logs:php-fpm', function () {
    run('tail -f /var/log/fpm-php.www.log');
})->verbose();

desc('Installs Composer');
task('provision:composer', function () {
    run('curl -sS https://getcomposer.org/installer | php');
    run('mv composer.phar /usr/local/bin/composer');
})->oncePerNode();

