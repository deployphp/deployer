<?php

namespace Deployer;

require __DIR__ . '/provision/databases.php';
require __DIR__ . '/provision/nodejs.php';
require __DIR__ . '/provision/php.php';
require __DIR__ . '/provision/user.php';
require __DIR__ . '/provision/website.php';

use Deployer\Task\Context;

use function Deployer\Support\parse_home_dir;

add('recipes', ['provision']);

// Name of lsb_release like: focal, bionic, etc.
// As only Ubuntu 20.04 LTS is supported for provision should be the `focal`.
set('lsb_release', function () {
    return run("lsb_release -s -c");
});

desc('Provision the server');
task('provision', [
    'provision:check',
    'provision:configure',
    'provision:update',
    'provision:upgrade',
    'provision:install',
    'provision:ssh',
    'provision:firewall',
    'provision:user',
    'provision:php',
    'provision:node',
    'provision:databases',
    'provision:composer',
    'provision:server',
    'provision:website',
    'provision:verify',
]);

// Default user to use for provisioning.
set('provision_user', 'root');

desc('Checks pre-required state');
task('provision:check', function () {
    set('remote_user', get('provision_user'));

    $release = run('cat /etc/os-release');
    ['NAME' => $name, 'VERSION_ID' => $version] = parse_ini_string($release);
    if ($name !== 'Ubuntu') {
        warning('!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!');
        warning('!!                                    !!');
        warning('!!      Only Ubuntu is supported!     !!');
        warning('!!                                    !!');
        warning('!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!');
        if (!askConfirmation(' Do you want to continue? (Not recommended)', false)) {
            throw new \RuntimeException('Provision aborted due to incompatible OS.');
        }
    }
    // Also only version 20 and older are supported.
    if (version_compare($version, '20', '<')) {
        warning("Ubuntu $version is not supported. Use Ubuntu 20 or newer.");
        if (!askConfirmation(' Do you want to continue? (Not recommended)', false)) {
            throw new \RuntimeException('Provision aborted due to incompatible OS.');
        }
    }
})->oncePerNode();

desc('Collects required params');
task('provision:configure', function () {
    set('remote_user', get('provision_user'));

    $params = [
        'sudo_password',
        'domain',
        'public_path',
        'php_version',
        'db_type',
    ];
    $dbparams = [
        'db_user',
        'db_name',
        'db_password',
    ];

    $showCode = false;

    foreach ($params as $name) {
        if (!Context::get()->getConfig()->hasOwn($name)) {
            $showCode = true;
        }
        get($name);
    }

    if (get('db_type') !== 'none') {
        foreach ($dbparams as $name) {
            if (!Context::get()->getConfig()->hasOwn($name)) {
                $showCode = true;
            }
            get($name);
        }
    }

    if ($showCode) {
        $code = "\n\n<comment>====== Configuration Start ======</comment>";
        $code .= "\nhost(<info>'{{alias}}'</info>)";
        $codeParams = $params;
        if (get('db_type') !== 'none') {
            $codeParams = array_merge($codeParams, $dbparams);
        }
        foreach ($codeParams as $name) {
            $code .= "\n    ->set(<info>'$name'</info>, <info>'" . get($name) . "'</info>)";
        }
        $code .= ";\n";
        $code .= "<comment>====== Configuration End ======</comment>\n\n";
        writeln($code);
    }
});


desc('Adds repositories and update');
task('provision:update', function () {
    set('remote_user', get('provision_user'));

    // Update before installing anything
    run('apt-get update', env: ['DEBIAN_FRONTEND' => 'noninteractive']);

    // Pre-requisites
    run('apt install -y curl gpg software-properties-common', env: ['DEBIAN_FRONTEND' => 'noninteractive']);

    // PHP
    run('apt-add-repository ppa:ondrej/php -y', env: [
        'DEBIAN_FRONTEND' => 'noninteractive',
        'LC_ALL' => 'C.UTF-8',
    ]);

    // Caddy
    run("curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | gpg --dearmor --yes -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg");
    run("curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' > /etc/apt/sources.list.d/caddy-stable.list");

    // Update
    run('apt-get update', env: ['DEBIAN_FRONTEND' => 'noninteractive']);
})
    ->oncePerNode()
    ->verbose();

desc('Upgrades all packages');
task('provision:upgrade', function () {
    set('remote_user', get('provision_user'));
    run('apt-get upgrade -y', env: ['DEBIAN_FRONTEND' => 'noninteractive'], timeout: 900);
})
    ->oncePerNode()
    ->verbose();

desc('Installs packages');
task('provision:install', function () {
    set('remote_user', get('provision_user'));
    $packages = [
        'acl',
        'apt-transport-https',
        'build-essential',
        'caddy',
        'curl',
        'debian-archive-keyring',
        'debian-keyring',
        'fail2ban',
        'gcc',
        'git',
        'libmcrypt4',
        'libpcre3-dev',
        'libsqlite3-dev',
        'make',
        'ncdu',
        'nodejs',
        'pkg-config',
        'python-is-python3',
        'redis',
        'sendmail',
        'sqlite3',
        'ufw',
        'unzip',
        'uuid-runtime',
        'whois',
    ];
    run('apt-get install -y ' . implode(' ', $packages), env: ['DEBIAN_FRONTEND' => 'noninteractive'], timeout: 900);
})
    ->verbose()
    ->oncePerNode();

desc('Configures the ssh');
task('provision:ssh', function () {
    set('remote_user', get('provision_user'));
    run("sed -i 's/PasswordAuthentication .*/PasswordAuthentication no/' /etc/ssh/sshd_config");
    run('ssh-keygen -A');
    run('service ssh restart');
    if (test('[ ! -d /root/.ssh ]')) {
        run('mkdir -p /root/.ssh');
        run('touch /root/.ssh/authorized_keys');
    }
})->oncePerNode();

desc('Setups a firewall');
task('provision:firewall', function () {
    set('remote_user', get('provision_user'));
    run('ufw allow 22');
    run('ufw allow 80');
    run('ufw allow 443');
    run('ufw --force enable');
})->oncePerNode();

desc('Verifies what provision was successful');
task('provision:verify', function () {
    fetch('{{domain}}', 'get', [], null, $info, true);
    if ($info['http_code'] === 404) {
        info("provisioned successfully!");
    }
});
