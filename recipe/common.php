<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Deployer\Functions;

/**
 * Common parameters.
 */
Functions\set('keep_releases', 3);
Functions\set('shared_dirs', []);
Functions\set('shared_files', []);
Functions\set('copy_dirs', []);
Functions\set('writable_dirs', []);
Functions\set('writable_use_sudo', true); // Using sudo in writable commands?

/**
 * Environment vars
 */
Functions\env('timezone', 'UTC');
Functions\env('branch', ''); // Branch to deploy.
Functions\env('env_vars', ''); // For Composer installation. Like SYMFONY_ENV=prod
Functions\env('composer_options', 'install --no-dev --verbose --prefer-dist --optimize-autoloader --no-progress --no-interaction');
Functions\env('git_cache', function () { //whether to use git cache - faster cloning by borrowing objects from existing clones.
    $gitVersion = Functions\run('git version');
    $regs       = [];
    if (preg_match('/((\d+\.?)+)/', $gitVersion, $regs)) {
        $version = $regs[1];
    } else {
        $version = "1.0.0";
    }
    return version_compare($version, '2.3', '>=');
});

/**
 * Default arguments and options.
 */
Functions\argument('stage', \Symfony\Component\Console\Input\InputArgument::OPTIONAL, 'Run tasks only on this server or group of servers.');
Functions\option('tag', null, \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL, 'Tag to deploy.');

/**
 * Rollback to previous release.
 */
Functions\task('rollback', function () {
    $releases = Functions\env('releases_list');

    if (isset($releases[1])) {
        $releaseDir = "{{deploy_path}}/releases/{$releases[1]}";

        // Symlink to old release.
        Functions\run("cd {{deploy_path}} && ln -nfs $releaseDir current");

        // Remove release
        Functions\run("rm -rf {{deploy_path}}/releases/{$releases[0]}");

        if (Functions\isVerbose()) {
            Functions\writeln("Rollback to `{$releases[1]}` release was successful.");
        }
    } else {
        Functions\writeln("<comment>No more releases you can revert to.</comment>");
    }
})->desc('Rollback to previous release');


/**
 * Preparing server for deployment.
 */
Functions\task('deploy:prepare', function () {
    \Deployer\Task\Context::get()->getServer()->connect();

    // Check if shell is POSIX-compliant
    try {
        Functions\cd(''); // To run command as raw.
        Functions\run('echo $0');
    } catch (\RuntimeException $e) {
        $formatter = \Deployer\Deployer::get()->getHelper('formatter');

        $errorMessage = [
            "Shell on your server is not POSIX-compliant. Please change to sh, bash or similar.",
            "Usually, you can change your shell to bash by running: chsh -s /bin/bash",
        ];
        Functions\write($formatter->formatBlock($errorMessage, 'error', true));

        throw $e;
    }

    // Set the deployment timezone
    if (!date_default_timezone_set(Functions\env('timezone'))) {
        date_default_timezone_set('UTC');
    }

    Functions\run('if [ ! -d {{deploy_path}} ]; then mkdir -p {{deploy_path}}; fi');

    // Create releases dir.
    Functions\run("cd {{deploy_path}} && if [ ! -d releases ]; then mkdir releases; fi");

    // Create shared dir.
    Functions\run("cd {{deploy_path}} && if [ ! -d shared ]; then mkdir shared; fi");
})->desc('Preparing server for deploy');

/**
 * Return release path.
 */
Functions\env('release_path', function () {
    return str_replace("\n", '', Functions\run("readlink {{deploy_path}}/release"));
});

/**
 * Release
 */
Functions\task('deploy:release', function () {
    $release = date('YmdHis');

    $releasePath = "{{deploy_path}}/releases/$release";

    $i = 0;
    while (is_dir(Functions\env()->parse($releasePath)) && $i < 42) {
        $releasePath .= '.' . ++$i;
    }

    Functions\run("mkdir $releasePath");

    Functions\run("cd {{deploy_path}} && if [ -h release ]; then rm release; fi");

    Functions\run("ln -s $releasePath {{deploy_path}}/release");
})->desc('Prepare release');


/**
 * Update project code
 */
Functions\task('deploy:update_code', function () {
    $repository = Functions\get('repository');
    $branch = Functions\env('branch');
    $gitCache = Functions\env('git_cache');
    $depth = $gitCache ? '' : '--depth 1';
    
    if (Functions\input()->hasOption('tag')) {
        $tag = Functions\input()->getOption('tag');
    }

    $at = '';
    if (!empty($tag)) {
        $at = "-b $tag";
    } elseif (!empty($branch)) {
        $at = "-b $branch";
    }

    $releases = Functions\env('releases_list');
    
    if ($gitCache && isset($releases[1])) {
        try {
            Functions\run("git clone $at --recursive -q --reference {{deploy_path}}/releases/{$releases[1]} --dissociate $repository  {{release_path}} 2>&1");
        } catch (RuntimeException $exc) {
            // If {{deploy_path}}/releases/{$releases[1]} has a failed git clone, is empty, shallow etc, git would throw error and give up. So we're forcing it to act without reference in this situation
            Functions\run("git clone $at --recursive -q $repository {{release_path}} 2>&1");
        }
    } else {
        // if we're using git cache this would be identical to above code in catch - full clone. If not, it would create shallow clone.
        Functions\run("git clone $at $depth --recursive -q $repository {{release_path}} 2>&1");
    }

})->desc('Updating code');

/**
 * Copy directories. Usefull for vendors directories
 */
Functions\task('deploy:copy_dirs', function () {

    $dirs = Functions\get('copy_dirs');

    foreach ($dirs as $dir) {
        //Delete directory if exists
        Functions\run("if [ -d $(echo {{release_path}}/$dir) ]; then rm -rf {{release_path}}/$dir; fi");

        //Copy directory
        Functions\run("if [ -d $(echo {{deploy_path}}/current/$dir) ]; then cp -rpf {{deploy_path}}/current/$dir {{release_path}}/$dir; fi");
    }

})->desc('Copy directories');

/**
 * Create symlinks for shared directories and files.
 */
Functions\task('deploy:shared', function () {
    $sharedPath = "{{deploy_path}}/shared";

    foreach (Functions\get('shared_dirs') as $dir) {
        // Remove from source
        Functions\run("if [ -d $(echo {{release_path}}/$dir) ]; then rm -rf {{release_path}}/$dir; fi");

        // Create shared dir if it does not exist
        Functions\run("mkdir -p $sharedPath/$dir");

        // Create path to shared dir in release dir if it does not exist
        // (symlink will not create the path and will fail otherwise)
        Functions\run("mkdir -p `dirname {{release_path}}/$dir`");

        // Symlink shared dir to release dir
        Functions\run("ln -nfs $sharedPath/$dir {{release_path}}/$dir");
    }

    foreach (Functions\get('shared_files') as $file) {
        // Remove from source
        Functions\run("if [ -f $(echo {{release_path}}/$file) ]; then rm -rf {{release_path}}/$file; fi");

        // Create dir of shared file
        Functions\run("mkdir -p $sharedPath/" . dirname($file));

        // Touch shared
        Functions\run("touch $sharedPath/$file");

        // Symlink shared dir to release dir
        Functions\run("ln -nfs $sharedPath/$file {{release_path}}/$file");
    }
})->desc('Creating symlinks for shared files');


/**
 * Make writable dirs.
 */
Functions\task('deploy:writable', function () {
    $dirs = join(' ', Functions\get('writable_dirs'));
    $sudo = Functions\get('writable_use_sudo') ? 'sudo' : '';

    if (!empty($dirs)) {
        try {
            $httpUser = Functions\run("ps aux | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | grep -v root | head -1 | cut -d\  -f1")->toString();

            Functions\cd('{{release_path}}');

            if (strpos(Functions\run("chmod 2>&1; true"), '+a') !== false) {
                if (!empty($httpUser)) {
                    Functions\run("$sudo chmod +a \"$httpUser allow delete,write,append,file_inherit,directory_inherit\" $dirs");
                }

                Functions\run("$sudo chmod +a \"`whoami` allow delete,write,append,file_inherit,directory_inherit\" $dirs");
            } elseif (Functions\commandExist('setfacl')) {
                if (!empty($httpUser)) {
                    Functions\run("$sudo setfacl -R -m u:\"$httpUser\":rwX -m u:`whoami`:rwX $dirs");
                    Functions\run("$sudo setfacl -dR -m u:\"$httpUser\":rwX -m u:`whoami`:rwX $dirs");
                } else {
                    Functions\run("$sudo chmod 777 -R $dirs");
                }
            } else {
                Functions\run("$sudo chmod 777 -R $dirs");
            }
        } catch (\RuntimeException $e) {
            $formatter = \Deployer\Deployer::get()->getHelper('formatter');

            $errorMessage = [
                "Unable to setup correct permissions for writable dirs.                  ",
                "You need co configure sudo's sudoers files to don't prompt for password,",
                "or setup correct permissions manually.                                  ",
            ];
            Functions\write($formatter->formatBlock($errorMessage, 'error', true));

            throw $e;
        }
    }

})->desc('Make writable dirs');


/**
 * Installing vendors tasks.
 */
Functions\task('deploy:vendors', function () {
    if (Functions\commandExist('composer')) {
        $composer = 'composer';
    } else {
        Functions\run("cd {{release_path}} && curl -sS https://getcomposer.org/installer | php");
        $composer = 'php composer.phar';
    }

    $composerEnvVars = Functions\env('env_vars') ? 'export ' . Functions\env('env_vars') . ' &&' : '';
    Functions\run("cd {{release_path}} && $composerEnvVars $composer {{composer_options}}");

})->desc('Installing vendors');


/**
 * Create symlink to last release.
 */
Functions\task('deploy:symlink', function () {
    Functions\run("cd {{deploy_path}} && ln -sfn {{release_path}} current"); // Atomic override symlink.
    Functions\run("cd {{deploy_path}} && rm release"); // Remove release link.
})->desc('Creating symlink to release');


/**
 * Return list of releases on server.
 */
Functions\env('releases_list', function () {
    $list = Functions\run('ls {{deploy_path}}/releases')->toArray();

    rsort($list);

    return $list;
});


/**
 * Return current release path.
 */
Functions\env('current', function () {
    return Functions\run("readlink {{deploy_path}}/current")->toString();
});

/**
 * Show current release number.
 */
Functions\task('current', function () {
    Functions\writeln('Current release: ' . basename(Functions\env('current')));
})->desc('Show current release.');


/**
 * Cleanup old releases.
 */
Functions\task('cleanup', function () {
    $releases = Functions\env('releases_list');

    $keep = Functions\get('keep_releases');

    while ($keep > 0) {
        array_shift($releases);
        --$keep;
    }

    foreach ($releases as $release) {
        Functions\run("rm -rf {{deploy_path}}/releases/$release");
    }

    Functions\run("cd {{deploy_path}} && if [ -e release ]; then rm release; fi");
    Functions\run("cd {{deploy_path}} && if [ -h release ]; then rm release; fi");

})->desc('Cleaning up old releases');


/**
 * Success message
 */
Functions\task('success', function () {
    Functions\writeln("<info>Successfully deployed!</info>");
})
    ->once()
    ->setPrivate();
