<?php
namespace Deployer;

set('websites', function () {
    info('You can configure multiple websites on one server.');
    writeln("");
    writeln("    set(<info>'websites'</info>, [...]);");
    writeln("");
    writeln("Read mode on how to configure websites on https://deployer.org.");
    $domain = ask(' Domain: ');
    if (is_string($domain)) {
        $domain = [$domain];
    }
    return $domain;
});

desc('Provision websites');
task('provision:websites', function () {
    set('remote_user', 'deployer');
    foreach (get('websites') as $website) {
        if (test("[ ! -d ~/$website ]")) {
            run("mkdir ~/$website");
        }
        info("Website $website configured!");
        writeln("");
        writeln("    set(<info>'deploy_path'</info>, <info>'/home/deployer/websites'</info>);");
        writeln("");
    }
});
