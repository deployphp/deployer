<?php

declare(strict_types=1);

namespace Deployer;

set('domain', function () {
    return ask(' Domain: ', get('hostname'));
});

set('public_path', function () {
    return ask(' Public path: ', 'public');
});

desc('Configures a server');
task('provision:server', function () {
    set('remote_user', get('provision_user'));
    run('setsebool -P httpd_enable_homedirs on');
    run("mkdir -p /var/deployer");
    $html = file_get_contents(__DIR__ . '/404.html');
    run("echo $'$html' > /var/deployer/404.html");
})->oncePerNode();

desc('Provision website');
task('provision:website', function () {
    $restoreBecome = become('deployer');

    run("[ -d {{deploy_path}} ] || mkdir -p {{deploy_path}}");
    run("chown -R deployer:deployer {{deploy_path}}");

    set('deploy_path', run("realpath {{deploy_path}}"));
    cd('{{deploy_path}}');

    run("[ -d log ] || mkdir log");
    run("chgrp caddy log");
    run("chmod g+w log");

    $caddyfile = parse(file_get_contents(__DIR__ . '/Caddyfile'));

    if (test('[ -f Caddyfile ]')) {
        run("echo $'$caddyfile' > Caddyfile.new");
        $diff = run('diff -U5 --color=always Caddyfile Caddyfile.new', nothrow: true);
        if (empty($diff)) {
            run('rm Caddyfile.new');
        } else {
            info('Found Caddyfile changes');
            writeln("\n" . $diff);
            $answer = askChoice(' Which Caddyfile to save? ', ['old', 'new'], 0);
            if ($answer === 'old') {
                run('rm Caddyfile.new');
            } else {
                run('mv Caddyfile.new Caddyfile');
            }
        }
    } else {
        run("echo $'$caddyfile' > Caddyfile");
    }

    $restoreBecome();

    // Caddy runs confined under the httpd_t SELinux domain. deploy_path lives
    // under the deployer user's home (user_home_t), which httpd_t can't read
    // or write, so relabel the whole tree as webserver content.
    run("semanage fcontext -a -t httpd_sys_rw_content_t '{{deploy_path}}(/.*)?'", nothrow: true);
    run('restorecon -R {{deploy_path}}');

    if (!test("grep -q 'import {{deploy_path}}/Caddyfile' /etc/caddy/Caddyfile")) {
        run("echo 'import {{deploy_path}}/Caddyfile' >> /etc/caddy/Caddyfile");
    }
    run('systemctl reload caddy');

    info("Website {{domain}} configured!");
})->limit(1);

desc('Shows access logs');
task('logs:access', function () {
    run('tail -f {{deploy_path}}/log/access.log');
})->verbose();

desc('Shows caddy syslog');
task('logs:caddy', function () {
    run('sudo journalctl -u caddy -f');
})->verbose();
