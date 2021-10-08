<?php
namespace Deployer;

require __DIR__ . '/php.php';
require __DIR__ . '/website.php';

use Deployer\Exception\GracefulShutdownException;
use function Deployer\Support\parse_home_dir;

add('recipes', ['provision']);

desc('Provision the server');
task('provision', [
    'provision:check',
    'provision:add-repositories',
    'provision:upgrade',
    'provision:essentials',
    'provision:ssh',
    'provision:firewall',
    'provision:deployer',
    'provision:php',
    'provision:composer',
    'provision:websites',
]);

desc('Check pre-required state');
task('provision:check', function () {
    $ok = true;
    $release = run('cat /etc/os-release');
    ['NAME' => $name, 'VERSION_ID' => $version] = parse_ini_string($release);

    if ($name !== 'Ubuntu' || $version !== '20.04') {
        $ok = false;
        warning('Only Ubuntu 20.04 LTS supported.');
    }

    if (!$ok) {
        throw new GracefulShutdownException('Missing some pre-required state. Please check warnings.');
    }
});

desc('Upgrade all packages');
task('provision:upgrade', function () {
    run('apt-get update', ['env' => ['DEBIAN_FRONTEND' => 'noninteractive']]);
    run('apt-get upgrade -y', ['env' => ['DEBIAN_FRONTEND' => 'noninteractive']]);
})->verbose();

task('provision:add-repositories', function () {
    run('apt-add-repository ppa:ondrej/php -y', ['env' => ['DEBIAN_FRONTEND' => 'noninteractive']]);
    run("curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' > /etc/apt/trusted.gpg.d/caddy-stable.asc");
    run("curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' > /etc/apt/sources.list.d/caddy-stable.list");
});

desc('Install essential packages');
task('provision:essentials', function () {
    $packages = [
        'apt-transport-https',
        'build-essential',
        'curl',
        'debian-archive-keyring',
        'debian-keyring',
        'fail2ban',
        'gcc',
        'git',
        'libmcrypt4',
        'libpcre3-dev',
        'make',
        'ncdu',
        'pkg-config',
        'sendmail',
        'ufw',
        'unzip',
        'uuid-runtime',
        'whois',
    ];
    run('apt-get install -y ' . implode(' ', $packages), ['env' => ['DEBIAN_FRONTEND' => 'noninteractive']]);
});


desc('Configure SSH');
task('provision:ssh', function () {
    run("sed -i 's/PasswordAuthentication .*/PasswordAuthentication no/' /etc/ssh/sshd_config");
    run('ssh-keygen -A');
    run('service ssh restart');
    if (test('[ ! -d /root/.ssh ]')) {
        run('mkdir -p /root/.ssh');
        run('touch /root/.ssh/authorized_keys');
    }
});

set('sudo_password', function () {
    info('Configure sudo_password:');
    writeln("");
    writeln("    set(<info>'sudo_password'</info>, ...);");
    writeln("");
    return askHiddenResponse(' Password for sudo: ');
});

// Specify which key to copy to server.
// Set to `false` to disable copy of key.
set('ssh_copy_id', '~/.ssh/id_rsa.pub');

desc('Setup deployer user');
task('provision:deployer', function () {
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

        if (!empty(get('ssh_copy_id'))) {
            $file = get('ssh_copy_id');
            if (!file_exists($file)) {
                info('Configure path to your public key.');
                writeln("");
                writeln("    set(<info>'ssh_copy_id'</info>, <info>'~/.ssh/id_rsa.pub'</info>);");
                writeln("");
                $file = ask(' Specify path to your public ssh key: ', '~/.ssh/id_rsa.pub');
            }
            run('echo "$KEY" >> /root/.ssh/authorized_keys', ['env' => ['KEY' => file_get_contents(parse_home_dir($file))]]);
        }
        run('cp /root/.ssh/authorized_keys /home/deployer/.ssh/authorized_keys');

        run('ssh-keygen -f /home/deployer/.ssh/id_rsa -t rsa -N ""');

        run('chown -R deployer:deployer /home/deployer');
        run('chmod -R 755 /home/deployer');
        run('chmod 700 /home/deployer/.ssh/id_rsa');

        run('echo "deployer ALL=NOPASSWD: /usr/sbin/service php-fpm reload" > /etc/sudoers.d/php-fpm');

        run('usermod -a -G www-data deployer');
        run('groups deployer');
    }
});

desc('Setup firewall');
task('provision:firewall', function () {
    run('ufw allow 22');
    run('ufw allow 80');
    run('ufw allow 443');
    run('ufw --force enable');
});
