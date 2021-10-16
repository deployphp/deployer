<?php
namespace Deployer;

set('db_type', function () {
    $supportedDbTypes = [
        'none',
        'mysql',
        'mariadb',
        'postgresql',
    ];
    return askChoice(' What DB to install? ', $supportedDbTypes, 0);
});

set('db_name', function () {
    return ask(' DB name: ');
});

set('db_password', function () {
    return askHiddenResponse(' DB password: ');
});

desc('Provision databases');
task('provision:databases', function () {
    $dbType = get('db_type');
    if ($dbType === 'none') {
        return;
    }
    get('db_name');
    get('db_password');
    invoke('provision:' . $dbType);
})
    ->limit(1);
