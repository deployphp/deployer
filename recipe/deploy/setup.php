<?php
namespace Deployer;

desc('Prepares host for deploy');
task('deploy:setup', function () {
    run(<<<EOF
[ -d {{deploy_path}} ] || mkdir -p {{deploy_path}};
cd {{deploy_path}};
[ -d .dep ] || mkdir .dep;
[ -d releases ] || mkdir releases;
[ -d shared ] || mkdir shared;
EOF
);

    // If current_path points to something like "/var/www/html", make sure it is
    // a symlink and not a directory.
    if (test('[ ! -L {{current_path}} ] && [ -d {{current_path}} ]')) {
        throw error("There is a directory (not symlink) at {{current_path}}.\n Remove this directory so it can be replaced with a symlink for atomic deployments.");
    }
});
