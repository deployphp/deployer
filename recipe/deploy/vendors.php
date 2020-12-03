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
set('composer_channel', null);

set('bin/composer', function () {
    $composerVersionToInstall = get('composer_version', null);
    $composerChannelToInstall = get('composer_channel', null);
    $composerValidChannels = ['stable', 'snapshot', 'preview', '1', '2',];
    if (!in_array((string)$composerChannelToInstall, $composerValidChannels, true)) {
        throw new \Exception('Selected Composer channel ' . $composerChannelToInstall . ' is not valid. Valid channels are: ' . implode(', ', $composerValidChannels));
    }

    $composerBin = null;
    if (commandExist('composer')) {
        $composerBin = locateBinaryPath('composer');
    }

    if (test('[ -f {{deploy_path}}/.dep/composer.phar ]')) {
        $composerBin = '{{deploy_path}}/.dep/composer.phar';
    }

    if ($composerBin) {
        $currentComposerVersionRaw = run('{{bin/php}} ' . $composerBin . ' --version');
        if (preg_match('/(\\d+\\.\\d+)(\\.\\d+)?-?(RC|alpha|beta|dev)?\d*/', $currentComposerVersionRaw, $composerVersionMatches)) {
            $currentComposerVersion = $composerVersionMatches[0] ?? null;
            if ($currentComposerVersion) {
                if ($composerVersionToInstall && $currentComposerVersion === (string)$composerVersionToInstall) {
                    return '{{bin/php} ' . $composerBin;
                }
                if ($composerChannelToInstall && !$composerVersionToInstall) {
                    if (preg_match('/\\+(.*)\\)/', $currentComposerVersionRaw, $snapshotVersion)) {
                        $currentComposerVersion = $snapshotVersion[1] ?? null;
                    }
                    $composerChannelData = json_decode(file_get_contents('https://getcomposer.org/versions'), true);
                    $latestComposerVersionFromChannel = $composerChannelData[$composerChannelToInstall][0]['version'] ?? null;
                    if ($latestComposerVersionFromChannel && $latestComposerVersionFromChannel === $currentComposerVersion) {
                        return '{{bin/php} ' . $composerBin;
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
