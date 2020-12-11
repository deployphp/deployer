<?php

namespace Deployer;

set('composer_action', 'install');

set('composer_options', '--verbose --prefer-dist --no-progress --no-interaction --no-dev --optimize-autoloader');

/**
 * Can be used to choose what composer version to install.
 * Valid values are any that are [listed here](https://getcomposer.org/download/).
 *
 * For example:
 * ```php
 *     set('composer_version', '10.10.15')
 * ```
 */
set('composer_version', null);

/**
 * Set this variable to stable, snapshot, preview, 1 or 2 to select which Composer channel to use
 */
set('composer_channel', 'stable');

set('bin/composer', function () {
    $composerVersionToInstall = get('composer_version');
    $composerChannelToInstall = get('composer_channel');

    $composerBin = null;
    if (commandExist('composer')) {
        $composerBin = '{{bin/php}} ' . locateBinaryPath('composer');
    }

    if (test('[ -f {{deploy_path}}/.dep/composer.phar ]')) {
        $composerBin = '{{bin/php}} {{deploy_path}}/.dep/composer.phar';
    }

    if ($composerBin) {
        // "composer --version" can return:
        // - "Composer version 1.10.17 2020-10-30 22:31:58" - for stable channel
        // - "Composer version 2.0.0-alpha3 2020-10-30 22:31:58" - alpha/beta/RC for preview channel
        // - "Composer version 2.0-dev (2.0-dev+378a5b72b9f81e8e919e41ecd3add6893d14b90e) 2020-12-04 09:50:19" - for snapshot channel
        $currentComposerVersionRaw = run($composerBin . ' --version');
        if (preg_match('/(\\d+\\.\\d+)(\\.\\d+)?-?(RC|alpha|beta|dev)?\d*/', $currentComposerVersionRaw, $composerVersionMatches)) {
            $currentComposerVersion = $composerVersionMatches[0] ?? null;
            if ($currentComposerVersion) {
                // if we have exact version of composer to install (composer_version) and currently installed version match it then return
                if ($composerVersionToInstall && $currentComposerVersion === (string)$composerVersionToInstall) {
                    return $composerBin;
                }
                if ($composerChannelToInstall && !$composerVersionToInstall) {
                    // for snapshot channel the version is the git hash in "Composer version 2.0-dev (2.0-dev+378a5b72b9f81e8e919e41ecd3add6893d14b90e) 2020-12-04 09:50:19"
                    if (preg_match('/\\+(.*)\\)/', $currentComposerVersionRaw, $snapshotVersion)) {
                        $currentComposerVersion = $snapshotVersion[1] ?? null;
                    }
                    // Compare latest version of composer channel with and currently installed version. If match then return.
                    $composerChannelData = json_decode(file_get_contents('https://getcomposer.org/versions'), true);
                    $latestComposerVersionFromChannel = $composerChannelData[$composerChannelToInstall][0]['version'] ?? null;
                    if ($latestComposerVersionFromChannel && $latestComposerVersionFromChannel === $currentComposerVersion) {
                        return $composerBin;
                    }
                }
            }
        }
    }

    $installCommand = "cd {{release_path}} && curl -sS https://getcomposer.org/installer | {{bin/php}}";
    if ($composerVersionToInstall) {
        $installCommand .= " -- --version=" . $composerVersionToInstall;
    } elseif ($composerChannelToInstall) {
        $installCommand .= " -- --" . $composerChannelToInstall;
    }

    run($installCommand);
    run('mv {{release_path}}/composer.phar {{deploy_path}}/.dep/composer.phar');
    return '{{bin/php}} {{deploy_path}}/.dep/composer.phar';
});

desc('Installing vendors');
task('deploy:vendors', function () {
    if (!commandExist('unzip')) {
        warning('To speed up composer installation setup "unzip" command with PHP zip extension.');
    }
    run('cd {{release_path}} && {{bin/composer}} {{composer_action}} {{composer_options}} 2>&1');
});
