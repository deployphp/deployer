<?php

namespace Deployer;

require __DIR__ . '/provision-fedora/databases.php';
require __DIR__ . '/provision-fedora/nodejs.php';
require __DIR__ . '/provision-fedora/php.php';
require __DIR__ . '/provision-fedora/user.php';
require __DIR__ . '/provision-fedora/website.php';

use Deployer\Task\Context;

use function Deployer\Support\parse_home_dir;

add('recipes', ['provision-fedora']);

// Fedora release number, like: 43, 44, etc.
// As only Fedora 43/44 are supported for provision should be one of those.
set('fedora_version', function () {
    return run('rpm -E %fedora');
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
    if ($name !== 'Fedora Linux') {
        warning('!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!');
        warning('!!                                    !!');
        warning('!!   Only Fedora Linux is supported!  !!');
        warning('!!                                    !!');
        warning('!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!');
        if (!askConfirmation(' Do you want to continue? (Not recommended)', false)) {
            throw new \RuntimeException('Provision aborted due to incompatible OS.');
        }
    }
    // Also only version 43 and newer are supported.
    if (version_compare($version, '43', '<')) {
        warning("Fedora $version is not supported. Use Fedora 43 or newer.");
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
    run('dnf -y makecache');

    // Pre-requisites
    run('dnf install -y curl gnupg2 dnf-plugins-core');

    // Caddy
    run('dnf copr enable -y @caddy/caddy');

    // Update
    run('dnf -y makecache');
})
    ->oncePerNode()
    ->verbose();

desc('Upgrades all packages');
task('provision:upgrade', function () {
    set('remote_user', get('provision_user'));
    run('dnf upgrade -y', timeout: 900);
})
    ->oncePerNode()
    ->verbose();

desc('Installs packages');
task('provision:install', function () {
    set('remote_user', get('provision_user'));
    $packages = [
        'acl',
        'caddy',
        'curl',
        'fail2ban',
        'firewalld',
        'gcc',
        'gcc-c++',
        'git',
        'make',
        'memcached',
        'ncdu',
        'nodejs',
        'openssh-server',
        'pcre-devel',
        'pkgconf-pkg-config',
        'policycoreutils-python-utils',
        'python3',
        'sendmail',
        'sqlite',
        'sqlite-devel',
        'unzip',
        'util-linux',
        'valkey',
        'whois',
    ];
    run('dnf install -y ' . implode(' ', $packages), timeout: 900);

    run('systemctl enable --now caddy');
    run('systemctl enable --now valkey');
    run('systemctl enable --now memcached');
    run('systemctl enable --now fail2ban');
})
    ->verbose()
    ->oncePerNode();

desc('Configures the ssh');
task('provision:ssh', function () {
    set('remote_user', get('provision_user'));
    run("sed -i 's/PasswordAuthentication .*/PasswordAuthentication no/' /etc/ssh/sshd_config");
    run('ssh-keygen -A');
    run('systemctl enable sshd');
    run('systemctl restart sshd');
    if (test('[ ! -d /root/.ssh ]')) {
        run('mkdir -p /root/.ssh');
        run('touch /root/.ssh/authorized_keys');
    }
})->oncePerNode();

desc('Setups a firewall');
task('provision:firewall', function () {
    set('remote_user', get('provision_user'));
    run('systemctl enable --now firewalld');
    run('firewall-cmd --permanent --add-service=ssh');
    run('firewall-cmd --permanent --add-service=http');
    run('firewall-cmd --permanent --add-service=https');
    run('firewall-cmd --reload');
})->oncePerNode();

desc('Verifies what provision was successful');
task('provision:verify', function () {
    fetch('{{domain}}', 'get', [], null, $info, true);
    if ($info['http_code'] === 404) {
        info("provisioned successfully!");
    }
});
