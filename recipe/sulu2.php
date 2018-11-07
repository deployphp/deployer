<?php

namespace Deployer;

require_once __DIR__ . '/symfony4.php';

set('shared_dirs', ['var/indexes', 'var/log', 'var/sessions', 'var/sitemaps', 'var/uploads', 'public/uploads']);

set('writable_dirs', ['var', 'public/uploads']);

set('bin/websiteconsole', function () {
    return parse('{{bin/php}} {{release_path}}/bin/websiteconsole --no-interaction');
});

desc('Migrate PHPCR');
task(
    'phpcr:migrate',
    function () {
        run('{{bin/php}} {{bin/console}} phpcr:migrations:migrate');
    }
);

desc('Clear cache');
task('deploy:website:cache:clear', function () {
    run('{{bin/websiteconsole}} cache:clear --no-warmup');
});

desc('Warm up cache');
task('deploy:website:cache:warmup', function () {
    run('{{bin/websiteconsole}} cache:warmup');
});

after('deploy:cache:clear', 'deploy:website:cache:clear');
after('deploy:website:cache:clear', 'deploy:website:cache:warmup');
