<?php declare(strict_types=1);
namespace Deployer;

set('domain', function () {
    return ask(' Domain: ');
});

set('public_path', function () {
    return ask(' Public path: ', 'public');
});

desc('Provision website');
task('provision:website', function () {
    set('remote_user', 'deployer');

    run("[ -d {{deploy_path}} ] || mkdir {{deploy_path}}");

    $domain = get('domain');
    $phpVersion = get('php_version');
    $deployPath = run("realpath {{deploy_path}}");
    $publicPath = get('public_path');

    cd($deployPath);

    run("[ -d log ] || mkdir log");
    run("chgrp caddy log");

    $caddyfile = <<<EOF
$domain {

\troot * $deployPath/current/$publicPath
\tfile_server
\tphp_fastcgi * unix//run/php/php$phpVersion-fpm.sock {
\t\tresolve_root_symlink
\t}

\tlog {
\t\toutput file $deployPath/log/access.log
\t}

\thandle_errors {
\t\t@404 {
\t\t\texpression {http.error.status_code} == 404
\t\t}
\t\trewrite @404 /404.html
\t\tfile_server {
\t\t\troot /var/dep/html
\t\t}
\t}
}
EOF;

    if (test('[ -f Caddyfile ]')) {
        run("echo $'$caddyfile' > Caddyfile.new");
        $diff = run('diff -U5 --color=always Caddyfile Caddyfile.new', ['no_throw' => true]);
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

    set('remote_user', 'root');
    if (!test("grep -q 'import $deployPath/Caddyfile' /etc/caddy/Caddyfile")) {
        run("echo 'import $deployPath/Caddyfile' >> /etc/caddy/Caddyfile");
    }
    run('service caddy reload');

    info("Website $domain configured!");
})->limit(1);

desc('Shows caddy logs');
task('logs:caddy', function () {
    run('tail -f {{deploy_path}}/log/access.log');
})->verbose();

desc('Shows caddy syslog');
task('logs:caddy:syslog', function () {
    run('sudo journalctl -u caddy -f');
})->verbose();

