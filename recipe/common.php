<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Common parameters.
 */
set('keep_releases', 3);
set('shared_dirs', []);
set('shared_files', []);
set('writable_dirs', []);
set('writable_use_sudo', true); // Using sudo in writable commands?

/**
 * Environment vars
 */
env('branch', ''); // Branch to deploy.
env('env_vars', ''); // For Composer installation. Like SYMFONY_ENV=prod
env('composer_options', 'install --no-dev --verbose --prefer-dist --optimize-autoloader --no-progress --no-interaction');

/**
 * Default arguments and options.
 */
argument('stage', \Symfony\Component\Console\Input\InputArgument::OPTIONAL, 'Run tasks only on this server or group of servers.');
option('tag', null, \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL, 'Tag to deploy.');

/**
 * Rollback to previous release.
 */
task('rollback', function () {
    $releases = env('releases_list');

    if (isset($releases[1])) {
        $releaseDir = "{{deploy_path}}/releases/{$releases[1]}";

        // Symlink to old release.
        run("cd {{deploy_path}} && ln -nfs $releaseDir current");

        // Remove release
        run("rm -rf {{deploy_path}}/releases/{$releases[0]}");

        if (isVerbose()) {
            writeln("Rollback to `{$releases[1]}` release was successful.");
        }
    } else {
        writeln("<comment>No more releases you can revert to.</comment>");
    }
})->desc('Rollback to previous release');


/**
 * Preparing server for deployment.
 */
task('deploy:prepare', function () {
    \Deployer\Task\Context::get()->getServer()->connect();

    // Check if shell is POSIX-compliant
    try {
        cd(''); // To run command as raw.
        run('echo $0');
    } catch (\RuntimeException $e) {
        $formatter = \Deployer\Deployer::get()->getHelper('formatter');

        $errorMessage = [
            "Shell on your server is not POSIX-compliant. Please change to sh, bash or similar.",
            "Usually, you can change your shell to bash by running: chsh -s /bin/bash",
        ];
        write($formatter->formatBlock($errorMessage, 'error', true));

        throw $e;
    }
    
    run('if [ ! -d {{deploy_path}} ]; then echo ""; fi');

    // Create releases dir.
    run("cd {{deploy_path}} && if [ ! -d releases ]; then mkdir releases; fi");

    // Create shared dir.
    run("cd {{deploy_path}} && if [ ! -d shared ]; then mkdir shared; fi");
})->desc('Preparing server for deploy');

/**
 * Return release path.
 */
env('release_path', function () {
    return str_replace("\n", '', run("readlink {{deploy_path}}/release"));
});

/**
 * Release
 */
task('deploy:release', function () {
    $release = run('date +%Y%m%d%H%M%S');

    $releasePath = "{{deploy_path}}/releases/$release";

    $i = 0;
    while (is_dir(env()->parse($releasePath)) && $i < 42) {
        $releasePath .= '.' . ++$i;
    }

    run("mkdir $releasePath");

    run("cd {{deploy_path}} && if [ -h release ]; then rm release; fi");

    run("ln -s $releasePath {{deploy_path}}/release");
})->desc('Prepare release');


/**
 * Update project code
 */
task('deploy:update_code', function () {
    $repository = get('repository');
    $branch = env('branch');
    if (input()->hasOption('tag')) {
        $tag = input()->getOption('tag');
    }

    $at = '';
    if (!empty($tag)) {
        $at = "-b $tag";
    } else if (!empty($branch)) {
        $at = "-b $branch";
    }

    run("git clone $at --depth 1 --recursive -q $repository {{release_path}} 2>&1");

})->desc('Updating code');


/**
 * Create symlinks for shared directories and files.
 */
task('deploy:shared', function () {
    $sharedPath = "{{deploy_path}}/shared";

    foreach (get('shared_dirs') as $dir) {
        // Remove from source
        run("if [ -d $(echo {{release_path}}/$dir) ]; then rm -rf {{release_path}}/$dir; fi");

        // Create shared dir if it does not exist
        run("mkdir -p $sharedPath/$dir");

        // Create path to shared dir in release dir if it does not exist
        // (symlink will not create the path and will fail otherwise)
        run("mkdir -p `dirname {{release_path}}/$dir`");

        // Symlink shared dir to release dir
        run("ln -nfs $sharedPath/$dir {{release_path}}/$dir");
    }

    foreach (get('shared_files') as $file) {
        // Remove from source
        run("if [ -f $(echo {{release_path}}/$file) ]; then rm -rf {{release_path}}/$file; fi");

        // Create dir of shared file
        run("mkdir -p $sharedPath/" . dirname($file));

        // Touch shared
        run("touch $sharedPath/$file");

        // Symlink shared dir to release dir
        run("ln -nfs $sharedPath/$file {{release_path}}/$file");
    }
})->desc('Creating symlinks for shared files');


/**
 * Make writable dirs.
 */
task('deploy:writable', function () {
    $dirs = join(' ', get('writable_dirs'));
    $sudo = get('writable_use_sudo') ? 'sudo' : '';

    if (!empty($dirs)) {

        try {
            $httpUser = run("ps aux | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | grep -v root | head -1 | cut -d\  -f1")->toString();

            cd('{{release_path}}');

            if (strpos(run("chmod 2>&1; true"), '+a') !== false) {

                if (!empty($httpUser)) {
                    run("$sudo chmod +a \"$httpUser allow delete,write,append,file_inherit,directory_inherit\" $dirs");
                }

                run("$sudo chmod +a \"`whoami` allow delete,write,append,file_inherit,directory_inherit\" $dirs");

            } elseif (commandExist('setfacl')) {

                if (!empty($httpUser)) {
                    run("$sudo setfacl -R -m u:\"$httpUser\":rwX -m u:`whoami`:rwX $dirs");
                    run("$sudo setfacl -dR -m u:\"$httpUser\":rwX -m u:`whoami`:rwX $dirs");
                } else {
                    run("$sudo chmod 777 $dirs");
                }


            } else {
                run("$sudo chmod 777 $dirs");
            }

        } catch (\RuntimeException $e) {
            $formatter = \Deployer\Deployer::get()->getHelper('formatter');

            $errorMessage = [
                "Unable to setup correct permissions for writable dirs.                  ",
                "You need co configure sudo's sudoers files to don't prompt for password,",
                "or setup correct permissions manually.                                  ",
            ];
            write($formatter->formatBlock($errorMessage, 'error', true));

            throw $e;
        }
    }

})->desc('Make writable dirs');


/**
 * Installing vendors tasks.
 */
task('deploy:vendors', function () {
    if (commandExist('composer')) {
        $composer = 'composer';
    } else {
        run("cd {{release_path}} && curl -sS https://getcomposer.org/installer | php");
        $composer = 'php composer.phar';
    }

    run("cd {{release_path}} && {{env_vars}} $composer {{composer_options}}");

})->desc('Installing vendors');


/**
 * Create symlink to last release.
 */
task('deploy:symlink', function () {
    run("cd {{deploy_path}} && ln -sfn {{release_path}} current"); // Atomic override symlink.
    run("cd {{deploy_path}} && rm release"); // Remove release link.
})->desc('Creating symlink to release');


/**
 * Return list of releases on server.
 */
env('releases_list', function () {
    $list = run('ls {{deploy_path}}/releases')->toArray();

    rsort($list);

    return $list;
});


/**
 * Return current release path.
 */
env('current', function () {
    return run("readlink {{deploy_path}}/current")->toString();
});

/**
 * Show current release number.
 */
task('current', function () {
    writeln('Current release: ' . basename(env('current')));
})->desc('Show current release.');


/**
 * Cleanup old releases.
 */
task('cleanup', function () {
    $releases = env('releases_list');

    $keep = get('keep_releases');

    while ($keep > 0) {
        array_shift($releases);
        --$keep;
    }

    foreach ($releases as $release) {
        run("rm -rf {{deploy_path}}/releases/$release");
    }

    run("cd {{deploy_path}} && if [ -e release ]; then rm release; fi");
    run("cd {{deploy_path}} && if [ -h release ]; then rm release; fi");

})->desc('Cleaning up old releases');


/**
 * Success message
 */
task('success', function () {
    writeln("<info>Successfully deployed!</info>");
})
    ->once()
    ->setPrivate();
