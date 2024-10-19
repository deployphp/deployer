<?php

declare(strict_types=1);

namespace Deployer;

set('domain', function () {
    return ask(' Domain: ');
});

set('public_path', function () {
    return ask(' Public path: ', 'public');
});

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
    run("mkdir -p /var/deployer");
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

    $caddyfile = parse(file_get_contents(__DIR__ . '/Caddyfile'));

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

    $restoreBecome();

    if (!test("grep -q 'import {{deploy_path}}/Caddyfile' /etc/caddy/Caddyfile")) {
        run("echo 'import {{deploy_path}}/Caddyfile' >> /etc/caddy/Caddyfile");
    }
    run('service caddy reload');

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
