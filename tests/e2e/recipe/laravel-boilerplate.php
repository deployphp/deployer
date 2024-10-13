<?php

declare(strict_types=1);

namespace Deployer;

require_once __DIR__ . '/hosts.php';
require_once __DIR__ . '/../../../recipe/laravel.php';

set('repository', 'https://github.com/deployphp/test-laravel.git');
set('branch', 'main');

task('laravel:setup-env', function (): void {
    $sharedPath = "{{deploy_path}}/shared";
    $envFile = "$sharedPath/.env";

    $releasePath = get('release_path');
    $envExampleFile = "$releasePath/.env.example";

    if (!test("[ -d $sharedPath ]")) {
        run("mkdir $sharedPath");
    }

    if (!test("[ -f $envFile ]")) {
        run("cp $envExampleFile $envFile");
    }
});

task('artisan:migrate')->disable();

before('deploy:shared', 'laravel:setup-env');
before('artisan:storage:link', 'artisan:key:generate');
