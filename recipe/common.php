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
set('copy_dirs', []);
set('writable_dirs', []);
set('use_sudo', true); // Using sudo in commands?

/**
 * Environment vars
 */
env('timezone', 'UTC');
env('branch', ''); // Branch to deploy.
env('env_vars', ''); // For Composer installation. Like SYMFONY_ENV=prod
env('composer_options', 'install --no-dev --verbose --prefer-dist --optimize-autoloader --no-progress --no-interaction');
env('git_cache', function () { //whether to use git cache - faster cloning by borrowing objects from existing clones.
    $gitVersion = run('git version');
    $regs       = [];
    if (preg_match('/((\d+\.?)+)/', $gitVersion, $regs)) {
        $version = $regs[1];
    } else {
        $version = '1.0.0';
    }
    return version_compare($version, '2.3', '>=');
});

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
        $releaseDir = sprintf('{{deploy_path}}/releases/%s', $releases[1]);

        // Symlink to old release.
        run(sprintf('cd {{deploy_path}} && %s ln -nfs %s current', useSudo(), $releaseDir));

        // Remove release
        run(sprintf('%s rm -rf {{deploy_path}}/releases/%s', useSudo(), $releases[0]));

        if (isVerbose()) {
            writeln(sprintf('Rollback to `%s` release was successful.', $releases[1]));
        }
    } else {
        writeln('<comment>No more releases you can revert to.</comment>');
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
            'Shell on your server is not POSIX-compliant. Please change to sh, bash or similar.',
            'Usually, you can change your shell to bash by running: chsh -s /bin/bash',
        ];
        write($formatter->formatBlock($errorMessage, 'error', true));

        throw $e;
    }

    // Set the deployment timezone
    if (!date_default_timezone_set(env('timezone'))) {
        date_default_timezone_set('UTC');
    }

    run(sprintf('if [ ! -d {{deploy_path}} ]; then %s mkdir -p {{deploy_path}}; fi', useSudo()));

    // Create releases dir.
    run(sprintf('cd {{deploy_path}} && if [ ! -d releases ]; then %s mkdir releases; fi', useSudo()));

    // Create shared dir.
    run(sprintf('cd {{deploy_path}} && if [ ! -d shared ]; then %s mkdir shared; fi', useSudo()));
})->desc('Preparing server for deploy');

/**
 * Return release path.
 */
env('release_path', function () {
    return str_replace('\n', '', run('readlink {{deploy_path}}/release'));
});

/**
 * Release
 */
task('deploy:release', function () {
    $release = date('YmdHis');

    $releasePath = sprintf('{{deploy_path}}/releases/%s', $release);

    $i = 0;
    while (is_dir(env()->parse($releasePath)) && $i < 42) {
        $releasePath .= '.' . ++$i;
    }

    run(sprintf('%s mkdir %s', useSudo(), $releasePath));

    run(sprintf('cd {{deploy_path}} && if [ -h release ]; then %s rm release; fi', useSudo()));

    run(sprintf('%s ln -s %s {{deploy_path}}/release', useSudo(), $releasePath));
})->desc('Prepare release');


/**
 * Update project code
 */
task('deploy:update_code', function () {
    $repository = get('repository');
    $branch = env('branch');
    $gitCache = env('git_cache');
    $depth = $gitCache ? '' : '--depth 1';

    if (input()->hasOption('tag')) {
        $tag = input()->getOption('tag');
    }

    $at = '';
    if (!empty($tag)) {
        $at = '-b '.$tag;
    } elseif (!empty($branch)) {
        $at = '-b '.$branch;
    }

    $releases = env('releases_list');

    if ($gitCache && isset($releases[1])) {
        try {
            run(sprintf('%s git clone %s --recursive -q --reference {{deploy_path}}/releases/%s --dissociate %s {{release_path}} 2>&1', useSudo(), $at, $releases[1], $repository));
        } catch (RuntimeException $exc) {
            // If {{deploy_path}}/releases/{$releases[1]} has a failed git clone, is empty, shallow etc, git would throw error and give up. So we're forcing it to act without reference in this situation
            run(sprintf('%s git clone %s --recursive -q %s {{release_path}} 2>&1', useSudo(), $at, $releases));
        }
    } else {
        // if we're using git cache this would be identical to above code in catch - full clone. If not, it would create shallow clone.
        run(sprintf('%s git clone %s %s --recursive -q %s {{release_path}} 2>&1', useSudo(), $at, $depth, $repository));
    }
})->desc('Updating code');

/**
 * Copy directories. Usefull for vendors directories
 */
task('deploy:copy_dirs', function () {
    $dirs = get('copy_dirs');

    foreach ($dirs as $dir) {
        //Delete directory if exists
        run(sprintf('if [ -d $(echo {{release_path}}/%s) ]; then %s rm -rf {{release_path}}/%s; fi',$dir, useSudo(), $dir));

        //Copy directory
        run(sprintf('if [ -d $(echo {{deploy_path}}/current/%s) ]; then %s cp -rpf {{deploy_path}}/current/%s {{release_path}}/%s; fi', $dir, useSudo(), $dir, $dir));
    }
})->desc('Copy directories');

/**
 * Create symlinks for shared directories and files.
 */
task('deploy:shared', function () {
    $sharedPath = '{{deploy_path}}/shared';

    foreach (get('shared_dirs') as $dir) {
        // Remove from source
        run(sprintf('if [ -d $(echo {{release_path}}/%s) ]; then %s rm -rf {{release_path}}/%s; fi', $dir, useSudo(), $dir));

        // Create shared dir if it does not exist
        run(sprintf('%s mkdir -p %s/%s', useSudo(), $sharedPath, $dir));

        // Create path to shared dir in release dir if it does not exist
        // (symlink will not create the path and will fail otherwise)
        run(sprintf('%s mkdir -p `dirname {{release_path}}/%s`', useSudo(), $dir));

        // Symlink shared dir to release dir
        run(sprintf('%s ln -nfs %s/%s {{release_path}}/%s ', useSudo(), $sharedPath, $dir, $dir));
    }

    foreach (get('shared_files') as $file) {
        // Remove from source
        run(sprintf('if [ -f $(echo {{release_path}}/%s) ]; then %s rm -rf {{release_path}}/%s; fi', $file, useSudo(), $file));

        // Create dir of shared file
        run(sprintf('%s mkdir -p %s/%s', useSudo(), $sharedPath, dirname($file)));

        // Touch shared
        run(sprintf('%s touch %s/%s', useSudo(), $sharedPath, $file));

        // Symlink shared dir to release dir
        run(sprintf('%s ln -nfs %s/%s {{release_path}}/%s', useSudo(), $sharedPath, $file, $file));
    }
})->desc('Creating symlinks for shared files');


/**
 * Make writable dirs.
 */
task('deploy:writable', function () {
    $dirs = join(' ', get('writable_dirs'));

    if (!empty($dirs)) {
        try {
            $httpUser = run('ps aux | grep -E \'[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx\' | grep -v root | head -1 | cut -d\  -f1')->toString();
            cd('{{release_path}}');

            if (strpos(run('chmod 2>&1; true'), '+a') !== false) {
                if (!empty($httpUser)) {
                    run(sprintf('%s chmod +a "%s allow delete,write,append,file_inherit,directory_inherit" %s', useSudo(), $httpUser, $dirs));
                }

                run(sprintf('%s chmod +a "`whoami` allow delete,write,append,file_inherit,directory_inherit" $dirs', useSudo(), $dirs));
            } elseif (commandExist('setfacl')) {
                if (!empty($httpUser)) {
                    run(sprintf('%s setfacl -R -m u:"%s":rwX -m u:`whoami`:rwX %s', useSudo(), $httpUser, $dirs));
                    run(sprintf('%s setfacl -dR -m u:"%s":rwX -m u:`whoami`:rwX %s', useSudo(), $httpUser, $dirs));
                } else {
                    run(sprintf('%s chmod 777 -R %s', useSudo(), $dirs));
                }
            } else {
                run(sprintf('%s chmod 777 -R %s', useSudo(), $dirs));
            }
        } catch (\RuntimeException $e) {
            $formatter = \Deployer\Deployer::get()->getHelper('formatter');

            $errorMessage = [
                'Unable to setup correct permissions for writable dirs.                  ',
                'You need co configure sudo\'s sudoers files to don\'t prompt for password,',
                'or setup correct permissions manually.                                  ',
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
        run(sprintf('cd {{release_path}} && %s curl -sS https://getcomposer.org/installer | php', useSudo()));
        $composer = 'php composer.phar';
    }

    $composerEnvVars = env('env_vars') ? 'export ' . env('env_vars') . ' &&' : '';
    run(sprintf('cd {{release_path}} && %s %s %s {{composer_options}}', useSudo(), $composerEnvVars, $composer));
})->desc('Installing vendors');


/**
 * Create symlink to last release.
 */
task('deploy:symlink', function () {
    run(sprintf('cd {{deploy_path}} && %s ln -sfn {{release_path}} current', useSudo())); // Atomic override symlink.
    run(sprintf('cd {{deploy_path}} && %s rm release', useSudo())); // Remove release link.
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
    return run('readlink {{deploy_path}}/current')->toString();
});

/**
 * Show current release number.
 */
task('current', function () {
    writeln('Current release: '.basename(env('current')));
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
        run(sprintf('%s rm -rf {{deploy_path}}/releases/%s', useSudo(), $release));
    }

    run(sprintf('cd {{deploy_path}} && if [ -e release ]; then %s rm release; fi', useSudo()));
    run(sprintf('cd {{deploy_path}} && if [ -h release ]; then %s rm release; fi', useSudo()));
})->desc('Cleaning up old releases');


/**
 * Success message
 */
task('success', function () {
    writeln('<info>Successfully deployed!</info>');
})->once()->setPrivate();
