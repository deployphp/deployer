<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;
require __DIR__ . '/common/config.php';

use Deployer\Task\Context;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Configuration
 */

set('keep_releases', 5);

set('repository', ''); // Repository to deploy.
set('branch', ''); // Branch to deploy.

set('shared_dirs', []);
set('shared_files', []);

set('copy_dirs', []);

set('writable_dirs', []);
set('writable_mode', 'chgrp'); // chgrp or acl.
set('writable_use_sudo', false); // Using sudo in writable commands?

set('http_user', false);

set('clear_paths', []);         // Relative path from deploy_path
set('clear_use_sudo', false);    // Using sudo in clean commands?

set('use_relative_symlink', true);

set('composer_action', 'install');
set('composer_options', '{{composer_action}} --verbose --prefer-dist --no-progress --no-interaction --no-dev --optimize-autoloader');

set('env_vars', ''); // Variable assignment before cmds (for example, SYMFONY_ENV={{set}})

set('git_cache', function () { //whether to use git cache - faster cloning by borrowing objects from existing clones.
    $gitVersion = run('{{bin/git}} version');
    $regs       = [];
    if (preg_match('/((\d+\.?)+)/', $gitVersion, $regs)) {
        $version = $regs[1];
    } else {
        $version = "1.0.0";
    }
    return version_compare($version, '2.3', '>=');
});

set('release_name', function () {
    $releasesList = array_map(function ($release) {
        return intval($release);
    }, array_filter(get('releases_list'), function ($release) {
        return preg_match("/^[0-9]+(\\/)?$/", $release) && !preg_match("/[0-9]{14}/", $release);
    }));

    $nextReleaseNumber = 1;
    if (count($releasesList) > 0) {
        $nextReleaseNumber = max($releasesList) + 1;
    }
    return (string)$nextReleaseNumber;
}); // name of folder in releases

/**
 * Return list of releases on server.
 */
set('releases_list', function () {
    // If there is no releases return empty list.
    if (!run('[ -d {{deploy_path}}/releases ] && [ "$(ls -A {{deploy_path}}/releases)" ] && echo "true" || echo "false"')->toBool()) {
        return [];
    }

    // Will list only dirs in releases and sort them by mtime in reverse.
    $list = run('cd {{deploy_path}}/releases && ls -t -d */')->toArray();

    // filter out anything that does not look like a release
    foreach ($list as $key => $item) {
        $item = basename(rtrim($item, '/')); // strip path returned from find

        $name_match = '[0-9_\.]+';
        $extension_match = '\.[0-9]+';
        if (!preg_match("/^$name_match($extension_match)*$/", $item)) {
            unset($list[$key]); // dir name does not match pattern, throw it out
            continue;
        }

        $list[$key] = $item; // $item was changed
    }

    return $list;
});

/**
 * Return release path.
 */
set('release_path', function () {
    $releaseExists = run("if [ -h {{deploy_path}}/release ]; then echo 'true'; fi")->toBool();
    if (!$releaseExists) {
        throw new \RuntimeException(
            "Release path does not found.\n" .
            "Run deploy:release to create new release."
        );
    }

    $link = run("readlink {{deploy_path}}/release")->toString();
    return substr($link, 0, 1) === '/' ? $link : get('deploy_path') . '/' . $link;
});


/**
 * Return current release path.
 */
set('current_path', function () {
    $link = run("readlink {{deploy_path}}/current")->toString();
    return substr($link, 0, 1) === '/' ? $link : get('deploy_path') . '/' . $link;
});


/**
 * Custom bins.
 */
set('bin/php', function () {
    return run('which php')->toString();
});
set('bin/git', function () {
    return run('which git')->toString();
});
set('bin/composer', function () {
    if (commandExist('composer')) {
        $composer = run('which composer')->toString();
    }

    if (empty($composer)) {
        run("cd {{release_path}} && curl -sS https://getcomposer.org/installer | {{bin/php}}");
        $composer = '{{bin/php}} {{release_path}}/composer.phar';
    }

    return $composer;
});
set('bin/symlink', function () {
    if (get('use_relative_symlink')) {
        // Check if target system supports relative symlink.
        if (run('if [[ "$(man ln)" =~ "--relative" ]]; then echo "true"; fi')->toBool()) {
            return 'ln -nfs --relative';
        }
    }
    return 'ln -nfs';
});

/**
 * Default arguments and options.
 */
argument('stage', InputArgument::OPTIONAL, 'Run tasks only on this server or group of servers');
option('tag', null, InputOption::VALUE_OPTIONAL, 'Tag to deploy');
option('revision', null, InputOption::VALUE_OPTIONAL, 'Revision to deploy');
option('branch', null, InputOption::VALUE_OPTIONAL, 'Branch to deploy');


desc('Rollback to previous release');
task('rollback', function () {
    $releases = get('releases_list');

    if (isset($releases[1])) {
        $releaseDir = "{{deploy_path}}/releases/{$releases[1]}";

        // Symlink to old release.
        run("cd {{deploy_path}} && {{bin/symlink}} $releaseDir current");

        // Remove release
        run("rm -rf {{deploy_path}}/releases/{$releases[0]}");

        if (isVerbose()) {
            writeln("Rollback to `{$releases[1]}` release was successful.");
        }
    } else {
        writeln("<comment>No more releases you can revert to.</comment>");
    }
});


desc('Lock deploy');
task('deploy:lock', function () {
    $locked = run("if [ -f {{deploy_path}}/deploy.lock ]; then echo 'true'; fi")->toBool();

    if ($locked) {
        throw new \RuntimeException(
            "Deploy locked.\n" .
            "Run deploy:unlock command to unlock."
        );
    } else {
        run("touch {{deploy_path}}/deploy.lock");
    }
});


desc('Unlock deploy');
task('deploy:unlock', function () {
    run("rm {{deploy_path}}/deploy.lock");
});


desc('Preparing server for deploy');
task('deploy:prepare', function () {
    // Check if shell is POSIX-compliant
    try {
        cd(''); // To run command as raw.
        $result = run('echo $0')->toString();
        if ($result == 'stdin: is not a tty') {
            throw new \RuntimeException(
                "Looks like ssh inside another ssh.\n" .
                "Help: http://goo.gl/gsdLt9"
            );
        }
    } catch (\RuntimeException $e) {
        $formatter = Deployer::get()->getHelper('formatter');

        $errorMessage = [
            "Shell on your server is not POSIX-compliant. Please change to sh, bash or similar.",
            "Usually, you can change your shell to bash by running: chsh -s /bin/bash",
        ];
        write($formatter->formatBlock($errorMessage, 'error', true));

        throw $e;
    }

    run('if [ ! -d {{deploy_path}} ]; then mkdir -p {{deploy_path}}; fi');

    // Check for existing /current directory (not symlink)
    $result = run('if [ ! -L {{deploy_path}}/current ] && [ -d {{deploy_path}}/current ]; then echo true; fi')->toBool();
    if ($result) {
        throw new \RuntimeException('There already is a directory (not symlink) named "current" in ' . get('deploy_path') . '. Remove this directory so it can be replaced with a symlink for atomic deployments.');
    }

    // Create releases dir.
    run("cd {{deploy_path}} && if [ ! -d releases ]; then mkdir releases; fi");

    // Create shared dir.
    run("cd {{deploy_path}} && if [ ! -d shared ]; then mkdir shared; fi");
});


desc('Prepare release');
task('deploy:release', function () {
    // Clean up if there is unfinished release.
    $previousReleaseExist = run("cd {{deploy_path}} && if [ -h release ]; then echo 'true'; fi")->toBool();

    if ($previousReleaseExist) {
        run('cd {{deploy_path}} && rm -rf "{{release_path}}"'); // Delete release.
        run('cd {{deploy_path}} && rm release'); // Delete symlink.
    }

    $releasePath = Context::get()->getEnvironment()->parse("{{deploy_path}}/releases/{{release_name}}");
    $i = 0;
    while (run("if [ -d $releasePath ]; then echo 'true'; fi")->toBool()) {
        $releasePath .= '.' . ++$i;
    }

    // Make new release.
    run("mkdir $releasePath");
    run("{{bin/symlink}} $releasePath {{deploy_path}}/release");
});


desc('Update code');
task('deploy:update_code', function () {
    $repository = trim(get('repository'));
    $branch = get('branch');
    $git = get('bin/git');
    $gitCache = get('git_cache');
    $depth = $gitCache ? '' : '--depth 1';

    // If option `branch` is set.
    if (input()->hasOption('branch')) {
        $branch = input()->getOption('branch');
    }

    // Branch may come from option or from configuration.
    $at = '';
    if (!empty($branch)) {
        $at = "-b $branch";
    }

    // If option `tag` is set
    if (input()->hasOption('tag')) {
        $tag = input()->getOption('tag');
        if (!empty($tag)) {
            $at = "-b $tag";
        }
    }

    // If option `tag` is not set and option `revision` is set
    if (empty($tag) && input()->hasOption('revision')) {
        $revision = input()->getOption('revision');
        if (!empty($revision)) {
            $depth = '';
        }
    }

    $releases = get('releases_list');

    if ($gitCache && isset($releases[1])) {
        try {
            run("$git clone $at --recursive -q --reference {{deploy_path}}/releases/{$releases[1]} --dissociate $repository  {{release_path}} 2>&1");
        } catch (\RuntimeException $exc) {
            // If {{deploy_path}}/releases/{$releases[1]} has a failed git clone, is empty, shallow etc, git would throw error and give up. So we're forcing it to act without reference in this situation
            run("$git clone $at --recursive -q $repository {{release_path}} 2>&1");
        }
    } else {
        // if we're using git cache this would be identical to above code in catch - full clone. If not, it would create shallow clone.
        run("$git clone $at $depth --recursive -q $repository {{release_path}} 2>&1");
    }
    
    if (!empty($revision)) {
        run("cd {{release_path}} && $git checkout $revision");
    }
});


desc('Copy directories');
task('deploy:copy_dirs', function () {
    $dirs = get('copy_dirs');

    foreach ($dirs as $dir) {
        // Delete directory if exists.
        run("if [ -d $(echo {{release_path}}/$dir) ]; then rm -rf {{release_path}}/$dir; fi");

        // Copy directory.
        run("if [ -d $(echo {{deploy_path}}/current/$dir) ]; then cp -rpf {{deploy_path}}/current/$dir {{release_path}}/$dir; fi");
    }
});


desc('Creating symlinks for shared files and dirs');
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
        run("{{bin/symlink}} $sharedPath/$dir {{release_path}}/$dir");
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
        run("{{bin/symlink}} $sharedPath/$file {{release_path}}/$file");
    }
});


desc('Make writable dirs');
task('deploy:writable', function () {
    $dirs = join(' ', get('writable_dirs'));
    $mode = get('writable_mode');
    $sudo = get('writable_use_sudo') ? 'sudo' : '';
    $httpUser = get('http_user', false);

    if (empty($dirs)) {
        return;
    }

    if ($httpUser === false && $mode !== '777') {
        // Detect http user in process list.
        $httpUser = run("ps axo user,comm | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | grep -v root | head -1 | cut -d\\  -f1")->toString();

        if (empty($httpUser)) {
            throw new \RuntimeException(
                "Can't detect http user name.\n" .
                "Please setup `http_user` config parameter."
            );
        }
    }

    try {
        cd('{{release_path}}');

        if ($mode === 'chgrp') {
            // Change group ownership.
            // -R   operate on files and directories recursively
            // -H   if a command line argument is a symbolic link to a directory, traverse it
            run("$sudo chgrp -RH $httpUser $dirs");
        } elseif ($mode === 'acl') {
            if (strpos(run("chmod 2>&1; true"), '+a') !== false) {
                // Try OS-X specific setting of access-rights

                run("$sudo chmod +a \"$httpUser allow delete,write,append,file_inherit,directory_inherit\" $dirs");
                run("$sudo chmod +a \"`whoami` allow delete,write,append,file_inherit,directory_inherit\" $dirs");
            } elseif (commandExist('setfacl')) {
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
                // If we are not on OS-X and have no ACL installed.
                // Maybe it's better to throw an exception.
                run("$sudo chmod -R 777 $dirs");
            }
        } elseif ($mode === '777') {
            run("$sudo chmod -R 777 $dirs");
        } else {
            throw new \RuntimeException("Unknown writable_mode `$mode`.");
        }
    } catch (\RuntimeException $e) {
        $formatter = Deployer::get()->getHelper('formatter');

        $errorMessage = [
            "Unable to setup correct permissions for writable dirs.                  ",
            "You need to configure sudo's sudoers files to not prompt for password,",
            "or setup correct permissions manually.                                  ",
        ];
        write($formatter->formatBlock($errorMessage, 'error', true));

        throw $e;
    }
});


desc('Installing vendors');
task('deploy:vendors', function () {
    run('cd {{release_path}} && {{env_vars}} {{bin/composer}} {{composer_options}}');
});


desc('Creating symlink to release');
task('deploy:symlink', function () {
    if (run('if [[ "$(man mv)" =~ "--no-target-directory" ]]; then echo "true"; fi')->toBool()) {
        run("mv -T {{deploy_path}}/release {{deploy_path}}/current");
    } else {
        // Atomic symlink does not supported.
        // Will use simple≤ two steps switch.

        run("cd {{deploy_path}} && {{bin/symlink}} {{release_path}} current"); // Atomic override symlink.
        run("cd {{deploy_path}} && rm release"); // Remove release link.
    }
});


desc('Show current release');
task('current', function () {
    writeln('Current release: ' . basename(get('current_path')));
});


desc('Cleaning up old releases');
task('cleanup', function () {
    $releases = get('releases_list');

    $keep = get('keep_releases');

    if ($keep === -1) {
        // Keep unlimited releases.
        return;
    }

    while ($keep > 0) {
        array_shift($releases);
        --$keep;
    }

    foreach ($releases as $release) {
        run("rm -rf {{deploy_path}}/releases/$release");
    }

    run("cd {{deploy_path}} && if [ -e release ]; then rm release; fi");
    run("cd {{deploy_path}} && if [ -h release ]; then rm release; fi");
});


desc('Cleaning up files and/or directories');
task('deploy:clean', function () {
    $paths = get('clear_paths');
    $sudo  = get('clear_use_sudo') ? 'sudo' : '';

    foreach ($paths as $path) {
        run("$sudo rm -rf {{release_path}}/$path");
    }
});

/**
 * Success message
 */
task('success', function () {
    Deployer::setDefault('terminate_message', '<info>Successfully deployed!</info>');
})->once()->setPrivate();
