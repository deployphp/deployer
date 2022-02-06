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
$domain

root * $deployPath/current/$publicPath
file_server
php_fastcgi * unix//run/php/php$phpVersion-fpm.sock {
\tresolve_root_symlink
}

log {
\toutput file $deployPath/log/access.log
}

handle_errors {
\t@404 {
\t\texpression {http.error.status_code} == 404
\t}
\trewrite @404 /404.html
\tfile_server {
\t\troot /var/dep/html
\t}
}
EOF;

    set('remote_user', 'root');
    cd('/etc/caddy/sites-enabled');

    if (test("[ -f $'$domain' ]")) {
        run("echo $'$caddyfile' >  $'$domain'.new");
        $diff = run("diff -U5 --color=always $'$domain' $'$domain'.new", ['no_throw' => true]);
        if (empty($diff)) {
            run("rm  $'$domain'.new");
        } else {
            info('Found Caddyfile changes');
            writeln("\n" . $diff);
            $answer = askChoice(' Which Caddyfile to save? ', ['old', 'new'], 0);
            if ($answer === 'old') {
                run("rm $'$domain'.new");
            } else {
                run("mv $'$domain'.new $'$domain'");
                run('service caddy reload');
            }
        }
    } else {
        run("echo $'$caddyfile' > $'$domain'");
        run('service caddy reload');
    }

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

