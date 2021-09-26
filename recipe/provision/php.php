<?php
namespace Deployer;

set('php_versions', function () {
    info('You can configure multiple PHP version on one server.');
    writeln("");
    writeln("    set(<info>'php_versions'</info>, [<info>'7.3'</info>, <info>'7.4'</info>, <info>'8.0'</info>]);");
    writeln("");
    $versions = ask(' What PHP version to install? ', '8.0', ['5.4', '7.2', '7.3', '7.4', '8.0']);
    if (is_string($versions)) {
        $versions = [$versions];
    }
    return $versions;
});

desc('Install PHP packages');
task('provision:php', function () {
    foreach (get('php_versions') as $version) {
        info("Installing PHP $version");
        $packages = [
            "php$version-bcmath",
            "php$version-cli",
            "php$version-curl",
            "php$version-dev",
            "php$version-fpm",
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
        run("sudo sed -i 's/;date.timezone.*/date.timezone = UTC/' /etc/php/$version/cli/php.ini");

        // Configure PHP-FPM
        run("sed -i 's/error_reporting = .*/error_reporting = E_ALL/' /etc/php/$version/fpm/php.ini");
        run("sed -i 's/display_errors = .*/display_errors = On/' /etc/php/$version/fpm/php.ini");
        run("sed -i 's/memory_limit = .*/memory_limit = 512M/' /etc/php/$version/fpm/php.ini");
        run("sed -i 's/;date.timezone.*/date.timezone = UTC/' /etc/php/$version/fpm/php.ini");
        run("sed -i 's/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/' /etc/php/$version/fpm/php.ini");

        // Configure FPM Pool
        run("sed -i 's/^user = www-data/user = deployer/' /etc/php/$version/fpm/pool.d/www.conf");
        run("sed -i 's/^group = www-data/group = deployer/' /etc/php/$version/fpm/pool.d/www.conf");
        run("sed -i 's/;listen\\.owner.*/listen.owner = deployer/' /etc/php/$version/fpm/pool.d/www.conf");
        run("sed -i 's/;listen\\.group.*/listen.group = deployer/' /etc/php/$version/fpm/pool.d/www.conf");
        run("sed -i 's/;listen\\.mode.*/listen.mode = 0666/' /etc/php/$version/fpm/pool.d/www.conf");
        run("sed -i 's/;request_terminate_timeout.*/request_terminate_timeout = 60/' /etc/php/$version/fpm/pool.d/www.conf");
    }

    // Configure PHP sessions directory
    run('chmod 733 /var/lib/php/sessions');
    run('chmod +t /var/lib/php/sessions');
});

desc('Install Composer');
task('provision:composer', function () {
    run('curl -sS https://getcomposer.org/installer | php');
    run('mv composer.phar /usr/local/bin/composer');
});

