<?php

namespace Deployer;

use function Deployer\Support\parse_home_dir;

set('sudo_password', function () {
    return askHiddenResponse(' Password for sudo: ');
});


desc('Setups a deployer user');
task('provision:user', function () {
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

        // Copy root public key to deployer user so user can login without password.
        run('cp /root/.ssh/authorized_keys /home/deployer/.ssh/authorized_keys');

        // Create ssh key if not already exists.
        run('ssh-keygen -f /home/deployer/.ssh/id_ed25519 -t ed25519 -N ""');

        run('chown -R deployer:deployer /home/deployer');
        run('chmod -R 755 /home/deployer');
        run('chmod 700 /home/deployer/.ssh/id_ed25519');

        run('usermod -a -G www-data deployer');
        run('usermod -a -G caddy deployer');
        run('groups deployer');
    }
})->oncePerNode();


desc('Copy public key to remote server');
task('provision:ssh_copy_id', function () {
    $defaultKeys = [
        '~/.ssh/id_rsa.pub',
        '~/.ssh/id_ed25519.pub',
        '~/.ssh/id_ecdsa.pub',
        '~/.ssh/id_dsa.pub',
    ];

    $publicKeyContent = false;
    foreach ($defaultKeys as $key) {
        $file = parse_home_dir($key);
        if (file_exists($file)) {
            $publicKeyContent = file_get_contents($file);
            break;
        }
    }

    if (!$publicKeyContent) {
        $publicKeyContent = ask(' Public key: ', '');
    }

    if (empty($publicKeyContent)) {
        info('Skipping public key copy as no public key was found or provided.');
        return;
    }

    run('echo "$PUBLIC_KEY" >> /home/deployer/.ssh/authorized_keys', [
        'env' => [
            'PUBLIC_KEY' => $publicKeyContent,
        ],
    ]);
});
