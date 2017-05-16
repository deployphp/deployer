<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Exception\RuntimeException;

desc('Update code');
task('deploy:update_code', function () {
    $repository = trim(get('repository'));
    $branch = get('branch');
    $git = get('bin/git');
    $gitCache = get('git_cache');
    $depth = $gitCache ? '' : '--depth 1';
    $options = [
        'tty' => get('git_tty', false),
    ];

    // If option `branch` is set.
    if (input()->hasOption('branch')) {
        $inputBranch = input()->getOption('branch');
        if (!empty($inputBranch)) {
            $branch = $inputBranch;
        }
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

    if ($gitCache && has('previous_release')) {
        try {
            run("$git clone $at --recursive -q --reference {{previous_release}} --dissociate $repository  {{release_path}} 2>&1", $options);
        } catch (RuntimeException $exc) {
            // If {{deploy_path}}/releases/{$releases[1]} has a failed git clone, is empty, shallow etc, git would throw error and give up. So we're forcing it to act without reference in this situation
            run("$git clone $at --recursive -q $repository {{release_path}} 2>&1", $options);
        }
    } else {
        // if we're using git cache this would be identical to above code in catch - full clone. If not, it would create shallow clone.
        run("$git clone $at $depth --recursive -q $repository {{release_path}} 2>&1", $options);
    }

    if (!empty($revision)) {
        run("cd {{release_path}} && $git checkout $revision");
    }
});
