<?php

namespace Deployer;

use Deployer\Exception\GracefulShutdownException;
use Symfony\Component\Console\Input\InputOption;
use function Deployer\Support\starts_with;

add('recipes', ['provision']);

set('php_version', '7.4');
set('sudo_password', function () {
    return askHiddenResponse('Type new password:');
});

desc('Provision server with nginx, php, php-fpm');
task('provision', [
    'provision:check',
    'provision:upgrade',
    'provision:install',
    'provision:ssh',
    'provision:ssh',
    'provision:user:deployer',
    'provision:firewall',
    'provision:install:php',
    'provision:install:composer',
    'provision:config:php-cli',
    'provision:config:php-fpm',
    'provision:config:php-fpm:pool',
    'provision:config:php:sessions',
    'provision:nginx:dhparam',
    'provision:nginx',
]);

desc('Check pre-required state');
task('provision:check', function () {
    $ok = true;
    if (get('php_version') !== '7.4') {
        $ok = false;
        warning("Only php 7.4 currently supported.");
    }

    $release = run('cat /etc/os-release');
    ['NAME' => $name, 'VERSION_ID' => $version] = parse_ini_string($release);

    if ($name !== 'Ubuntu' || $version !== '20.04') {
        $ok = false;
        warning('Only Ubuntu 20.04 LTS supported for now.');
    }

    if (!$ok) {
        throw new GracefulShutdownException('Missing some pre-required state. Please check warnings.');
    }
});

desc('Upgrade all packages');
task('provision:upgrade', function () {
    run('apt-get update', ['env' => ['DEBIAN_FRONTEND' => 'noninteractive']]);
    run('apt-get upgrade -y', ['env' => ['DEBIAN_FRONTEND' => 'noninteractive']]);
});

desc('Install base packages');
task('provision:install', function () {
    $packages = [
        'build-essential',
        'curl',
        'fail2ban',
        'gcc',
        'git',
        'libmcrypt4',
        'libpcre3-dev',
        'make',
        'ncdu',
        'nginx',
        'pkg-config',
        'sendmail',
        'ufw',
        'unzip',
        'uuid-runtime',
        'whois',
    ];
    run('apt-get install -y --allow-downgrades --allow-remove-essential --allow-change-held-packages ' . implode(' ', $packages), ['env' => ['DEBIAN_FRONTEND' => 'noninteractive']]);
});

desc('Configure SSH');
task('provision:ssh', function () {
    run('sed -i "/PasswordAuthentication yes/d" /etc/ssh/sshd_config');
    run('echo "" | sudo tee -a /etc/ssh/sshd_config');
    run('echo "" | sudo tee -a /etc/ssh/sshd_config');
    run('echo "PasswordAuthentication no" | sudo tee -a /etc/ssh/sshd_config');
    run('ssh-keygen -A');
    run('service ssh restart');
    if (test('[ ! -d /root/.ssh ]')) {
        run('mkdir -p /root/.ssh');
        run('touch /root/.ssh/authorized_keys');
    }
});

desc('Setup deployer user');
task('provision:user:deployer', function () {
    if (test('id deployer >/dev/null 2>&1')) {
        info('deployer user already exist');
    } else {
        run('useradd deployer');
        run('mkdir -p /home/deployer/.ssh');
        run('mkdir -p /home/deployer/.deployer');
        run('adduser deployer sudo');

        run('chsh -s /bin/bash deployer');
        run('cp /root/.profile /home/deployer/.profile');
        run('cp /root/.bashrc /home/deployer/.bashrc');

        $password = run("mkpasswd -m sha-512 '%secret%'", ['secret' => get('sudo_password')]);
        run("usermod --password '%secret%' deployer", ['secret' => $password]);

        // TODO: Copy current ssh-key.
        run('echo >> /root/.ssh/authorized_keys');
        run('cp /root/.ssh/authorized_keys /home/deployer/.ssh/authorized_keys');

        run('ssh-keygen -f /home/deployer/.ssh/id_rsa -t rsa -N ""');

        run('chown -R deployer:deployer /home/deployer');
        run('chmod -R 755 /home/deployer');
        run('chmod 700 /home/deployer/.ssh/id_rsa');

        run('echo "deployer ALL=NOPASSWD: /usr/sbin/service php-fpm reload" > /etc/sudoers.d/php-fpm');

        run('usermod -a -G www-data deployer');
        run('id deployer');
        run('groups deployer');
    }
});

desc('Setup firewall');
task('provision:firewall', function () {
    $firewallEnabled = get('firewall', true);

    if ($firewallEnabled) {
        run('ufw allow 22');
        run('ufw allow 80');
        run('ufw allow 443');
        run('ufw --force enable');
    } else {
        if (output()->isDebug()) {
            writeln("Skipping firewall setup");
        }
    }
});

desc('Install PHP packages');
task('provision:install:php', function () {
    $packages = [
        "php-bcmath",
        "php-cli",
        "php-curl",
        "php-dev",
        "php-fpm",
        "php-fpm",
        "php-gd",
        "php-imap",
        "php-intl",
        "php-mbstring",
        "php-mysql",
        "php-pgsql",
        "php-readline",
        "php-soap",
        "php-sqlite3",
        "php-xml",
        "php-zip",
    ];
    run('apt-get install -y --force-yes ' . implode(' ', $packages), ['env' => ['DEBIAN_FRONTEND' => 'noninteractive']]);
});


desc('Install Composer');
task('provision:install:composer', function () {
    run('curl -sS https://getcomposer.org/installer | php');
    run('mv composer.phar /usr/local/bin/composer');
});

desc('Configure PHP-CLI');
task('provision:config:php-cli', function () {
    run('sudo sed -i "s/error_reporting = .*/error_reporting = E_ALL/" /etc/php/{{php_version}}/cli/php.ini');
    run('sudo sed -i "s/display_errors = .*/display_errors = On/" /etc/php/{{php_version}}/cli/php.ini');
    run('sudo sed -i "s/memory_limit = .*/memory_limit = 512M/" /etc/php/{{php_version}}/cli/php.ini');
    run('sudo sed -i "s/;date.timezone.*/date.timezone = UTC/" /etc/php/{{php_version}}/cli/php.ini');
});

desc('Configure PHP-FPM');
task('provision:config:php-fpm', function () {
    run('sed -i "s/error_reporting = .*/error_reporting = E_ALL/" /etc/php/{{php_version}}/fpm/php.ini');
    run('sed -i "s/display_errors = .*/display_errors = On/" /etc/php/{{php_version}}/fpm/php.ini');
    run('sed -i "s/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/" /etc/php/{{php_version}}/fpm/php.ini');
    run('sed -i "s/memory_limit = .*/memory_limit = 512M/" /etc/php/{{php_version}}/fpm/php.ini');
    run('sed -i "s/;date.timezone.*/date.timezone = UTC/" /etc/php/{{php_version}}/fpm/php.ini');
});

desc('Configure FPM Pool');
task('provision:config:php-fpm:pool', function () {
    run('sed -i "s/^user = www-data/user = deployer/" /etc/php/{{php_version}}/fpm/pool.d/www.conf');
    run('sed -i "s/^group = www-data/group = deployer/" /etc/php/{{php_version}}/fpm/pool.d/www.conf');
    run('sed -i "s/;listen\.owner.*/listen.owner = deployer/" /etc/php/{{php_version}}/fpm/pool.d/www.conf');
    run('sed -i "s/;listen\.group.*/listen.group = deployer/" /etc/php/{{php_version}}/fpm/pool.d/www.conf');
    run('sed -i "s/;listen\.mode.*/listen.mode = 0666/" /etc/php/{{php_version}}/fpm/pool.d/www.conf');
    run('sed -i "s/;request_terminate_timeout.*/request_terminate_timeout = 60/" /etc/php/{{php_version}}/fpm/pool.d/www.conf');
});

desc('Configure php sessions directory');
task('provision:config:php:sessions', function () {
    run('chmod 733 /var/lib/php/sessions');
    run('chmod +t /var/lib/php/sessions');
});

desc('Generating DH (Diffie Hellman) key');
task('provision:nginx:dhparam', function () {
    if (test('[ -f /etc/nginx/dhparams.pem ]')) {
        info('/etc/nginx/dhparams.pem already exist');
    } else {
        info('Generating DH key, 2048 bit long safe prime');
        info('This is going to take a long time');
        run('openssl dhparam -out /etc/nginx/dhparams.pem 2048 2>/dev/null');
    }
});

desc('Install nginx & php-fpm');
task('provision:nginx', function () {
    run('systemctl enable nginx.service');

    run('sed -i "s/user www-data;/user deployer;/" /etc/nginx/nginx.conf');
    run('sed -i "s/worker_processes.*/worker_processes auto;/" /etc/nginx/nginx.conf');
    run('sed -i "s/# multi_accept.*/multi_accept on;/" /etc/nginx/nginx.conf');
    run('sed -i "s/# server_names_hash_bucket_size.*/server_names_hash_bucket_size 128;/" /etc/nginx/nginx.conf');

    run('cat > /etc/nginx/conf.d/gzip.conf << EOF
gzip_vary on;
gzip_proxied any;
gzip_comp_level 5;
gzip_min_length 256;

gzip_types application/atom+xml application/javascript application/json application/rss+xml application/vnd.ms-fontobject application/x-font-ttf application/x-web-app-manifest+json application/xhtml+xml application/xml font/opentype image/svg+xml image/x-icon text/css text/plain text/x-component;
EOF');

    run('cat > /etc/nginx/sites-available/default << EOF
server {
    return 404;
}
EOF');
    run('ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default');
    run('service nginx restart');

    run('service php{{php_version}}-fpm restart');
});
