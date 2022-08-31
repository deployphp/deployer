<?php
namespace Deployer;

require_once __DIR__ . '/symfony.php';

add('recipes', ['sulu']);

add('shared_dirs', ['var/indexes', 'var/sitemaps', 'var/uploads', 'public/uploads']);

add('writable_dirs', ['public/uploads']);

set('bin/websiteconsole', function () {
    return parse('{{bin/php}} {{release_or_current_path}}/bin/websiteconsole --no-interaction');
});

desc('Migrates PHPCR');
task('phpcr:migrate', function () {
    run('{{bin/console}} phpcr:migrations:migrate');
});

desc('Clears cache');
task('deploy:website:cache:clear', function () {
    run('{{bin/websiteconsole}} cache:clear --no-warmup');
});

desc('Warmups cache');
task('deploy:website:cache:warmup', function () {
    run('{{bin/websiteconsole}} cache:warmup');
});

after('deploy:cache:clear', 'deploy:website:cache:clear');
after('deploy:website:cache:clear', 'deploy:website:cache:warmup');
