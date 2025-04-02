<?php

namespace Deployer;

use Deployer\Exception\ConfigurationException;
use Deployer\Exception\Exception;
use Deployer\Exception\GracefulShutdownException;

// Cancel deployment if there would be no change to the codebase.
// This avoids unnecessary releases if the latest commit has already been deployed.
desc('Checks remote head');
task('deploy:check_remote', function () {
    $repository = get('repository');

    // Skip if there is no current deployment to compare
    if (get('update_code_strategy') === 'archive') {
        if (!test('[ -f {{current_path}}/REVISION ]')) {
            return;
        }
    } elseif (get('update_code_strategy') === 'clone') {
        if (!test('[ -d {{current_path}}/.git ]')) {
            return;
        }
    } else {
        throw new ConfigurationException(parse("Unknown `update_code_strategy` option: {{update_code_strategy}}."));
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
        $remoteLs = runLocally("git ls-remote $opt $repository $ref");
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

    if (get('update_code_strategy') === 'archive') {
        $lastDeployedRevision = run('cat {{current_path}}/REVISION');
    } elseif (get('update_code_strategy') === 'clone') {
        $lastDeployedRevision = trim(run(sprintf('cd {{current_path}} && %s rev-parse HEAD', get('bin/git'))));
    } else {
        throw new ConfigurationException(parse("Unknown `update_code_strategy` option: {{update_code_strategy}}."));
    }

    if ($targetRevision && strpos($lastDeployedRevision, $targetRevision) === 0) {
        throw new GracefulShutdownException("Already up-to-date.");
    }

    info("deployed different version");
});
