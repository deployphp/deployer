<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Exception\Exception;
use Deployer\Exception\GracefulShutdownException;

// Cancel deployment if there would be no change to the codebase.
// This avoids unnecessary releases if the latest commit has already been deployed.
desc('Check remote head');
task('deploy:check_remote', function () {
    $repository = get('repository');
    if (empty($repository)) {
        throw new Exception("You need to specify a repository.");
    }

    // Skip if there is no current deployment to compare
    if (! test('[ -d {{deploy_path}}/current/.git ]')) {
        return;
    }

    // Determine the hash of the remote revision about to be deployed
    $targetRevision = input()->getOption('revision');

    if (!$targetRevision) {
        $ref = 'HEAD';
        $opt = '';

        if ($tag = input()->getOption('tag')) {
            $ref = $tag;
            $opt = '--tags';
        } elseif ($branch = get('branch')) {
            $ref = $branch;
            $opt = '--heads';
        }

        $remoteLs = runLocally(sprintf("%s ls-remote $opt $repository $ref", get('bin/git')));
        if (strstr($remoteLs, "\n")) {
            throw new Exception("Could not determine target revision. '$ref' matched multiple commits.");
        }
        if (!$remoteLs) {
            throw new Exception("Could not resolve a revision from '$ref'.");
        }

        $targetRevision = substr($remoteLs, 0, strpos($remoteLs, "\t"));
    }

    // Compare commit hashes. We use strpos to support short versions.
    $targetRevision = trim($targetRevision);
    $lastDeployedRevision = trim(run(sprintf('cd {{deploy_path}}/current && %s rev-parse HEAD', get('bin/git'))));
    if ($targetRevision && strpos($lastDeployedRevision, $targetRevision) === 0) {
        throw new GracefulShutdownException("Already up-to-date.");
    }
});
