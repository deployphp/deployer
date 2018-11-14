<?php

namespace Deployer;

require_once __DIR__ . '/symfony3.php';

add('shared_dirs', ['var/indexes', 'var/sitemaps', 'var/uploads', 'web/uploads']);

add('writable_dirs', ['var/indexes', 'var/sitemaps', 'var/uploads', 'web/uploads']);

set('bin/websiteconsole', function () {
    return parse('{{bin/php}} {{release_path}}/bin/websiteconsole --no-interaction');
});

desc('Migrate PHPCR');
task(
    'phpcr:migrate',
    function () {
        run('{{bin/php}} {{bin/console}} phpcr:migrations:migrate {{console_options}}');
    }
);

desc('Clear cache');
task('deploy:website:cache:clear', function () {
    run('{{bin/websiteconsole}} cache:clear {{console_options}} --no-warmup');
});

desc('Warm up cache');
task('deploy:website:cache:warmup', function () {
    run('{{bin/websiteconsole}} cache:warmup {{console_options}}');
});

after('deploy:cache:clear', 'deploy:website:cache:clear');
after('deploy:website:cache:clear', 'deploy:website:cache:warmup');
