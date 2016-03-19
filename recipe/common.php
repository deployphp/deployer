<?php
/* (c) Anton Medvedev <anton@medv.io>
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
set('writable_use_sudo', true); // Using sudo in writable commands?
set('http_user', null);
set('composer_command', 'composer'); // Path to composer
set('clear_paths', []);         // Relative path from deploy_path
set('clear_use_sudo', true);    // Using sudo in clean commands?

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
        $version = "1.0.0";
    }
    return version_compare($version, '2.3', '>=');
});
env('release_name', date('YmdHis')); // name of folder in releases

/**
 * Default arguments and options.
 */
argument('stage', \Symfony\Component\Console\Input\InputArgument::OPTIONAL, 'Run tasks only on this server or group of servers.');
option('tag', null, \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL, 'Tag to deploy.');
option('revision', null, \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL, 'Revision to deploy.');

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
        $result = run('echo $0')->toString();
        if ($result == 'stdin: is not a tty') {
            throw new RuntimeException(
                "Looks like ssh inside another ssh.\n" .
                "Help: http://goo.gl/gsdLt9"
            );
        }
    } catch (\RuntimeException $e) {
        $formatter = \Deployer\Deployer::get()->getHelper('formatter');

        $errorMessage = [
            "Shell on your server is not POSIX-compliant. Please change to sh, bash or similar.",
            "Usually, you can change your shell to bash by running: chsh -s /bin/bash",
        ];
        write($formatter->formatBlock($errorMessage, 'error', true));

        throw $e;
    }

    // Set the deployment timezone
    if (!date_default_timezone_set(env('timezone'))) {
        date_default_timezone_set('UTC');
    }

    run('if [ ! -d {{deploy_path}} ]; then mkdir -p {{deploy_path}}; fi');

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
    $releasePath = "{{deploy_path}}/releases/{{release_name}}";

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
    $gitCache = env('git_cache');
    $depth = $gitCache ? '' : '--depth 1';

    if (input()->hasOption('tag')) {
        $tag = input()->getOption('tag');
    } elseif (input()->hasOption('revision')) {
        $revision = input()->getOption('revision');
    }

    $at = '';
    if (!empty($tag)) {
        $at = "-b $tag";
    } elseif (!empty($branch)) {
        $at = "-b $branch";
    }

    $releases = env('releases_list');

    if (!empty($revision)) {
        // To checkout specified revision we need to clone all tree.
        run("git clone $at --recursive -q $repository {{release_path}} 2>&1");
        run("cd {{release_path}} && git checkout $revision");
    } elseif ($gitCache && isset($releases[1])) {
        try {
            run("git clone $at --recursive -q --reference {{deploy_path}}/releases/{$releases[1]} --dissociate $repository  {{release_path}} 2>&1");
        } catch (RuntimeException $exc) {
            // If {{deploy_path}}/releases/{$releases[1]} has a failed git clone, is empty, shallow etc, git would throw error and give up. So we're forcing it to act without reference in this situation
            run("git clone $at --recursive -q $repository {{release_path}} 2>&1");
        }
    } else {
        // if we're using git cache this would be identical to above code in catch - full clone. If not, it would create shallow clone.
        run("git clone $at $depth --recursive -q $repository {{release_path}} 2>&1");
    }

})->desc('Updating code');

/**
 * Copy directories. Useful for vendors directories
 */
task('deploy:copy_dirs', function () {

    $dirs = get('copy_dirs');

    foreach ($dirs as $dir) {
        // Delete directory if exists.
        run("if [ -d $(echo {{release_path}}/$dir) ]; then rm -rf {{release_path}}/$dir; fi");

        // Copy directory.
        run("if [ -d $(echo {{deploy_path}}/current/$dir) ]; then cp -rpf {{deploy_path}}/current/$dir {{release_path}}/$dir; fi");
    }

})->desc('Copy directories');

/**
 * Create symlinks for shared directories and files.
 */
task('deploy:shared', function () {
    $sharedPath = "{{deploy_path}}/shared";

    foreach (get('shared_dirs') as $dir) {
        // Remove from source.
        run("if [ -d $(echo {{release_path}}/$dir) ]; then rm -rf {{release_path}}/$dir; fi");

        // Create shared dir if it does not exist.
        run("mkdir -p $sharedPath/$dir");

        // Create path to shared dir in release dir if it does not exist.
        // (symlink will not create the path and will fail otherwise)
        run("mkdir -p `dirname {{release_path}}/$dir`");

        // Symlink shared dir to release dir
        run("ln -nfs $sharedPath/$dir {{release_path}}/$dir");
    }

    foreach (get('shared_files') as $file) {
        $dirname = dirname($file);
        // Remove from source.
        run("if [ -f $(echo {{release_path}}/$file) ]; then rm -rf {{release_path}}/$file; fi");
        // Ensure dir is available in release
        run("if [ ! -d $(echo {{release_path}}/$dirname) ]; then mkdir -p {{release_path}}/$dirname;fi");

        // Create dir of shared file
        run("mkdir -p $sharedPath/" . $dirname);

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
    $httpUser = get('http_user');

    if (!empty($dirs)) {
        try {
            if (null === $httpUser) {
                $httpUser = run("ps aux | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | grep -v root | head -1 | cut -d\  -f1")->toString();
            }

            cd('{{release_path}}');

            // Try OS-X specific setting of access-rights
            if (strpos(run("chmod 2>&1; true"), '+a') !== false) {
                if (!empty($httpUser)) {
                    run("$sudo chmod +a \"$httpUser allow delete,write,append,file_inherit,directory_inherit\" $dirs");
                }

                run("$sudo chmod +a \"`whoami` allow delete,write,append,file_inherit,directory_inherit\" $dirs");
            // Try linux ACL implementation with unsafe fail-fallback to POSIX-way
            } elseif (commandExist('setfacl')) {
                if (!empty($httpUser)) {
                    if (!empty($sudo)) {
                        run("$sudo setfacl -R -m u:\"$httpUser\":rwX -m u:`whoami`:rwX $dirs");
                        run("$sudo setfacl -dR -m u:\"$httpUser\":rwX -m u:`whoami`:rwX $dirs");
                    } else {
                        // When running without sudo, exception may be thrown
                        // if executing setfacl on files created by http user (in directory that has been setfacl before).
                        // These directories/files should be skipped.
                        // Now, we will check each directory for ACL and only setfacl for which has not been set before.
                        $writeableDirs = get('writable_dirs');
                        foreach ($writeableDirs as $dir) {
                            // Check if ACL has been set or not
                            $hasfacl = run("getfacl -p $dir | grep \"^user:$httpUser:.*w\" | wc -l")->toString();
                            // Set ACL for directory if it has not been set before
                            if (!$hasfacl) {
                                run("setfacl -R -m u:\"$httpUser\":rwX -m u:`whoami`:rwX $dir");
                                run("setfacl -dR -m u:\"$httpUser\":rwX -m u:`whoami`:rwX $dir");
                            }
                        }
                    }
                } else {
                    run("$sudo chmod 777 -R $dirs");
                }
            // If we are not on OS-X and have no ACL installed use POSIX
            } else {
                run("$sudo chmod 777 -R $dirs");
            }
        } catch (\RuntimeException $e) {
            $formatter = \Deployer\Deployer::get()->getHelper('formatter');

            $errorMessage = [
                "Unable to setup correct permissions for writable dirs.                  ",
                "You need to configure sudo's sudoers files to not prompt for password,",
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
    $composer = get('composer_command');
    
    if (! commandExist($composer)) {
        run("cd {{release_path}} && curl -sS https://getcomposer.org/installer | php");
        $composer = 'php composer.phar';
    }

    $composerEnvVars = env('env_vars') ? 'export ' . env('env_vars') . ' &&' : '';
    run("cd {{release_path}} && $composerEnvVars $composer {{composer_options}}");

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
    // find will list only dirs in releases/
    $list = run('find {{deploy_path}}/releases -maxdepth 1 -mindepth 1 -type d')->toArray();

    // filter out anything that does not look like a release
    foreach ($list as $key => $item) {
        $item = basename($item); // strip path returned from find

        // release dir can look like this: 20160216152237 or 20160216152237.1.2.3.4 ...
        $name_match = '[0-9]{14}'; // 20160216152237
        $extension_match = '\.[0-9]+'; // .1 or .15 etc
        if (!preg_match("/^$name_match($extension_match)*$/", $item)) {
            unset($list[$key]); // dir name does not match pattern, throw it out
            continue;
        }

        $list[$key] = $item; // $item was changed
    }

    rsort($list);

    return $list;
});


/**
 * Return the current release timestamp
 */
env('release', function () {
    return basename(env('current'));
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
 * Cleanup files and directories
 */
task('deploy:clean', function () {
    $paths = get('clear_paths');
    $sudo  = get('clear_use_sudo') ? 'sudo' : '';

    foreach ($paths as $path) {
        run("$sudo rm -rf {{deploy_path}}/$path");
    }

})->desc('Cleaning up files and/or directories');

/**
 * Success message
 */
task('success', function () {
    writeln("<info>Successfully deployed!</info>");
})
    ->once()
    ->setPrivate();
