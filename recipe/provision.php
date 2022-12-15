<?php
namespace Deployer;

require __DIR__ . '/provision/databases.php';
require __DIR__ . '/provision/nodejs.php';
require __DIR__ . '/provision/php.php';
require __DIR__ . '/provision/website.php';

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
    'provision:deployer',
    'provision:server',
    'provision:php',
    'provision:databases',
    'provision:composer',
    'provision:npm',
    'provision:website',
    'provision:verify',
]);

desc('Checks pre-required state');
task('provision:check', function () {
    if (get('remote_user') !== 'root') {
        warning('');
        warning('Run provision as root: -o remote_user=root');
        warning('or with a sudo enabled user: -o become=root');
        warning('');
    }

    $release = run('cat /etc/os-release');
    ['NAME' => $name, 'VERSION_ID' => $version] = parse_ini_string($release);
    if ($name !== 'Ubuntu' || $version !== '20.04') {
        warning('!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!');
        warning('!!                                    !!');
        warning('!!  Only Ubuntu 20.04 LTS supported!  !!');
        warning('!!                                    !!');
        warning('!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!');
    }
})->oncePerNode();

desc('Collects required params');
task('provision:configure', function () {
    $params = [
        'sudo_password',
        'domain',
        'public_path',
        'php_version',
        'db_type',
        'db_user',
        'db_name',
        'db_password',
    ];
    $code = "\n\n    host(<info>'{{alias}}'</info>)";
    foreach ($params as $name) {
        $code .= "\n        ->set(<info>'$name'</info>, <info>'…'</info>)";
    }
    $code .= ";\n\n";
    writeln($code);
    foreach ($params as $name) {
        get($name);
    }
});

desc('Adds repositories and update');
task('provision:update', function () {
    // PHP
    run('apt-add-repository ppa:ondrej/php -y', ['env' => ['DEBIAN_FRONTEND' => 'noninteractive']]);

    // Caddy
    run("curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | gpg --dearmor --yes -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg");
    run("curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' > /etc/apt/sources.list.d/caddy-stable.list");

    // Nodejs
    $keyring = '/usr/share/keyrings/nodesource.gpg';
    run("curl -fsSL https://deb.nodesource.com/gpgkey/nodesource.gpg.key | gpg --dearmor | sudo tee '$keyring' >/dev/null");
    run("gpg --no-default-keyring --keyring '$keyring' --list-keys");
    run("echo 'deb [signed-by=$keyring] https://deb.nodesource.com/{{nodejs_version}} {{lsb_release}} main' | sudo tee /etc/apt/sources.list.d/nodesource.list");
    run("echo 'deb-src [signed-by=$keyring] https://deb.nodesource.com/{{nodejs_version}} {{lsb_release}} main' | sudo tee -a /etc/apt/sources.list.d/nodesource.list");

    // Update
    run('apt-get update', ['env' => ['DEBIAN_FRONTEND' => 'noninteractive']]);
})
    ->oncePerNode()
    ->verbose();

desc('Upgrades all packages');
task('provision:upgrade', function () {
    run('apt-get upgrade -y', ['env' => ['DEBIAN_FRONTEND' => 'noninteractive'], 'timeout' => 900]);
})
    ->oncePerNode()
    ->verbose();

desc('Installs packages');
task('provision:install', function () {
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
    run('apt-get install -y ' . implode(' ', $packages), ['env' => ['DEBIAN_FRONTEND' => 'noninteractive'], 'timeout' => 900]);
})
    ->verbose()
    ->oncePerNode();

desc('Configures a server');
task('provision:server', function () {
    run('usermod -a -G www-data caddy');
    $html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 Not Found</title>
    <style>
        body {
            -moz-osx-font-smoothing: grayscale;
            -webkit-font-smoothing: antialiased;
            align-content: center;
            background: #343434;
            color: #fff;
            display: grid;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
            font-size: 20px;
            justify-content: center;
            margin: 0;
            min-height: 100vh;
        }
        main {
            padding: 0 30px;
        }
        svg {
            animation: 2s ease-in-out infinite hover;
        }
        @keyframes hover {
            0%, 100% {
                transform: translateY(0)
            }
            50% {
                transform: translateY(-8px)
            }
        }
    </style>
</head>
<body>
<main>
    <svg width="48" height="38" viewBox="0 0 243 193">
        <title>Deployer</title>
        <g fill="none" fill-rule="evenodd">
            <path fill="#0CF" d="M242.781.39L.207 101.653l83.606 21.79z"/>
            <path fill="#00B3E0" d="M97.555 186.363l14.129-50.543L242.78.39 83.812 123.442l13.743 62.922"/>
            <path fill="#0084A6" d="M97.555 186.363l33.773-39.113-19.644-11.43-14.13 50.543"/>
            <path fill="#0CF" d="M131.328 147.25l78.484 45.664L242.782.391 111.683 135.82l19.644 11.429"/>
        </g>
    </svg>
    <h1>Not Found</h1>
    <p>The requested URL was not found on this server.</p>
</main>
</body>
</html>
HTML;
    run("mkdir -p /var/dep/html");
    run("echo $'$html' > /var/dep/html/404.html");
})->oncePerNode();

desc('Configures the ssh');
task('provision:ssh', function () {
    run("sed -i 's/PasswordAuthentication .*/PasswordAuthentication no/' /etc/ssh/sshd_config");
    run('ssh-keygen -A');
    run('service ssh restart');
    if (test('[ ! -d /root/.ssh ]')) {
        run('mkdir -p /root/.ssh');
        run('touch /root/.ssh/authorized_keys');
    }
})->oncePerNode();

set('sudo_password', function () {
    return askHiddenResponse(' Password for sudo: ');
});

// Specify which key to copy to server.
// Set to `false` to disable copy of key.
set('ssh_copy_id', '~/.ssh/id_rsa.pub');

desc('Setups a deployer user');
task('provision:deployer', function () {
    if (test('id deployer >/dev/null 2>&1')) {
        // TODO: Check what created deployer user configured correctly.
        // TODO: Update sudo_password of deployer user.
        // TODO: Copy ssh_copy_id to deployer ssh dir.
        info('deployer user already exist');
    } else {
        run('useradd deployer');
        run('mkdir -p /home/deployer/.ssh');
        run('mkdir -p /home/deployer/.deployer');
        run('adduser deployer sudo');

        run('chsh -s /bin/bash deployer');
        run('cp /root/.profile /home/deployer/.profile');
        run('cp /root/.bashrc /home/deployer/.bashrc');

        // Make color prompt.
        run("sed -i 's/#force_color_prompt=yes/force_color_prompt=yes/' /home/deployer/.bashrc");

        $password = run("mkpasswd -m sha-512 '%secret%'", ['secret' => get('sudo_password')]);
        run("usermod --password '%secret%' deployer", ['secret' => $password]);

        if (!empty(get('ssh_copy_id'))) {
            $file = parse_home_dir(get('ssh_copy_id'));
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

        run('usermod -a -G www-data deployer');
        run('usermod -a -G caddy deployer');
        run('groups deployer');
    }
})->oncePerNode();

desc('Setups a firewall');
task('provision:firewall', function () {
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
