<?php
/*
 * This recipe for work with ISPManager Lite panel by API.
 */
namespace Deployer;

use Deployer\Exception\Exception;
use Deployer\Utility\Httpie;

set('ispmanager_owner', 'www-root');
set('ispmanager_doc_root', '/var/www/' . get('ispmanager_owner') . '/data/');

// ISPManager default configuration
set('ispmanager', [
    'api' => [
        'dsn' => 'https://root:password@localhost:1500/ispmgr',
        'secure' => true,
    ],
    'createDomain' => NULL,
    'updateDomain' => NULL,
    'deleteDomain' => NULL,
    'createDatabase' => NULL,
    'deleteDatabase' => NULL,
    'phpSelect' => NULL,
    'createAlias' => NULL,
    'deleteAlias' => NULL,
]);

// Vhost default configuration
set('vhost', [
    'name' => '{{domain}}',
    'php_enable' => 'on',
    'aliases' => 'www.{{domain}}',
    'home' => 'www/{{domain}}',
    'owner' => get('ispmanager_owner'),
    'email' => 'webmaster@{{domain}}',
    'charset' => 'off',
    'dirindex' => 'index.php uploaded.html',
    'ssi' => 'on',
    'php' => 'on',
    'php_mode' => 'php_mode_mod',
    'basedir' => 'on',
    'php_apache_version' => 'native',
    'cgi' => 'off',
    'log_access' => 'on',
    'log_error' => 'on',
]);

// Storage
set('ispmanager_session', '');
set('ispmanager_databases', [
    'servers' => [],
    'hosts' => [],
    'dblist' => [],
]);

set('ispmanager_domains', []);
set('ispmanager_phplist', []);
set('ispmanager_aliaslist', []);

desc('Installs ispmanager');
task('ispmanager:init', function () {
    $config = get('ispmanager');

    if (!is_null($config['createDatabase']) || !is_null($config['deleteDatabase'])) {
        invoke('ispmanager:db-server-list');
        invoke('ispmanager:db-list');
    }

    if (!is_null($config['createDomain']) || !is_null($config['deleteDomain'])) {
        invoke('ispmanager:domain-list');
    }

    if (!is_null($config['phpSelect'])) {
        invoke('ispmanager:domain-list');
        invoke('ispmanager:get-php-list');
    }

    if (!is_null($config['createAlias']) || !is_null($config['deleteAlias'])) {
        invoke('ispmanager:domain-list');
    }
});

desc('Takes database servers list');
task('ispmanager:db-server-list', function () {
    $response = ispmanagerRequest('get', [
        'func' => 'db.server',
    ]);

    $hostList = [];
    $serverList = [];

    if (isset ($response['doc']['elem']) && count($response['doc']['elem']) > 0) {
        foreach ($response['doc']['elem'] as $dbServer) {
            $serverList[$dbServer['name']['$']] = [
                'host' => $dbServer['host']['$'],
                'name' => $dbServer['name']['$'],
                'version' => $dbServer['savedver']['$'],
            ];

            if (!strpos($dbServer['host']['$'], ':')) {
                $dbHost = $dbServer['host']['$'] . ':3306';
            } else {
                $dbHost = $dbServer['host']['$'];
            }

            $hostList[$dbHost] = [
                'host' => $dbHost,
                'name' => $dbServer['name']['$'],
                'version' => $dbServer['savedver']['$'],
            ];
        }
    }

    add('ispmanager_databases', [
        'servers' => $serverList,
        'hosts' => $hostList,
    ]);
});

desc('Takes databases list');
task('ispmanager:db-list', function () {
    $response = ispmanagerRequest('get', [
        'func' => 'db',
    ]);

    $dbList = [];
    if (isset ($response['doc']['elem']) && count($response['doc']['elem']) > 0) {
        foreach ($response['doc']['elem'] as $db) {
            $dbList[$db['pair']['$']] = [
                'name' => $db['name']['$'],
                'server' => $db['server']['$'],
                'location' => $db['pair']['$'],
            ];
        }
    }

    add('ispmanager_databases', [
        'dblist' => $dbList
    ]);
});

desc('Takes domain list');
task('ispmanager:domain-list', function () {
    $response = ispmanagerRequest('get', [
        'func' => 'webdomain',
    ]);

    $domainList = [];
    if (isset ($response['doc']['elem']) && count($response['doc']['elem']) > 0) {
        foreach ($response['doc']['elem'] as $domain) {
            $domainList[] = $domain['name']['$'];
        }
    }

    add('ispmanager_domains', $domainList);
});

desc('Creates new database');
task('ispmanager:db-create', function () {
    $config = get('ispmanager');

    if (is_null($config['createDatabase'])) {
        warning('Action for database create is not active');
        return;
    }

    $dsnData = parse_url($config['createDatabase']['dsn']);

    $dbInfo = get('ispmanager_databases');

    $hostInfo = NULL;
    foreach ($dbInfo['hosts'] as $hostData) {
        if ($hostData['host'] == $dsnData['host'] . ':' . $dsnData['port']) {
            $hostInfo = $hostData;
            break;
        }
    }

    if (is_null($hostInfo)) {
        throw new Exception('Incorrect DB host');
    }

    $dbName = substr($dsnData['path'], 1);

    $dbLocation = $dbName . '->' . $hostInfo['name'];

    if (isset ($dbInfo['dblist'][$dbLocation])) {
        if (!isset ($config['createDatabase']['skipIfExist']) || !$config['createDatabase']['skipIfExist']) {
            throw new Exception('Database already exists!');
        } else {
            warning('Database already exists - skipping');
            return;
        }
    }

    $dbCreateRequest = [
        'func' => 'db.edit',
        'name' => $dbName,
        'owner' => get('ispmanager_owner'),
        'server' => $hostInfo['name'],
        'charset' => $config['createDatabase']['charset'],
        'sok' => 'ok',
    ];

    if ($dsnData['user'] == '*') {
        $dbCreateRequest['user'] = '*';
        $dbCreateRequest['username'] = $dbName;

        if ($dsnData['pass'] == '*') {
            $dbCreateRequest['password'] = generatePassword(8);
        } else {
            $dbCreateRequest['password'] = $dsnData['pass'];
        }
    } else {
        $dbCreateRequest['user'] = $dsnData['user'];
    }


    $response = ispmanagerRequest('post', $dbCreateRequest);

    if (isset ($response['doc']['error']['msg']['$'])) {
        throw new Exception($response['doc']['error']['msg']['$']);
    } else {
        info('Database successfully created');
    }
});

desc('Deletes database');
task('ispmanager:db-delete', function () {
    $config = get('ispmanager');

    if (is_null($config['deleteDatabase'])) {
        warning('Action for database delete is not active');
        return;
    }

    $dbInfo = get('ispmanager_databases');
    $dsnData = parse_url($config['deleteDatabase']['dsn']);

    $hostInfo = NULL;
    foreach ($dbInfo['hosts'] as $hostData) {
        if ($hostData['host'] == $dsnData['host'] . ':' . $dsnData['port']) {
            $hostInfo = $hostData;
            break;
        }
    }

    if (is_null($hostInfo)) {
        throw new Exception('Incorrect DB host');
    }

    $dbName = substr($dsnData['path'], 1);

    $dbLocation = $dbName . '->' . $hostInfo['name'];

    if (!isset ($dbInfo['dblist'][$dbLocation])) {
        if (!isset ($config['deleteDatabase']['skipIfNotExist']) || !$config['deleteDatabase']['skipIfNotExist']) {
            throw new Exception('Database not exist!');
        } else {
            warning('Database not exist - skipping');
            return;
        }
    }

    $dbDeleteRequest = [
        'func' => 'db.delete',
        'elid' => $dbLocation,
    ];

    $response = ispmanagerRequest('post', $dbDeleteRequest);

    if (isset ($response['doc']['error']['msg']['$'])) {
        throw new Exception($response['doc']['error']['msg']['$']);
    } else {
        info('Database successfully deleted');
    }
});

desc('Creates new domain');
task('ispmanager:domain-create', function () {
    $config = get('ispmanager');

    if (is_null($config['createDomain'])) {
        warning('Action for domain create is not active');
        return;
    }

    if (!isset ($config['createDomain']['name']) || $config['createDomain']['name'] == '') {
        throw new Exception('Invalid domain name!');
    }

    // Check domain exists
    $existDomains = get('ispmanager_domains');

    if (in_array($config['createDomain']['name'], $existDomains)) {
        if (!isset ($config['createDomain']['skipIfExist']) || !$config['createDomain']['skipIfExist']) {
            throw new Exception('Domain already exists!');
        } else {
            warning('Domain already exists - skipping');
            return;
        }
    }

    // Build vhost create request
    $vhostTemplate = get('vhost');

    $domainCreateRequest = [
        'func' => 'webdomain.edit',
        'sok' => 'ok',
    ];

    foreach ($vhostTemplate as $key => $value) {
        $domainCreateRequest[$key] = str_replace('{{domain}}', $config['createDomain']['name'], $vhostTemplate[$key]);
    }

    $response = ispmanagerRequest('post', $domainCreateRequest);

    if (isset ($response['doc']['error']['msg']['$'])) {
        throw new Exception($response['doc']['error']['msg']['$']);
    } else {
        info('Domain successfully created');
    }
});

desc('Gets allowed PHP modes and versions');
task('ispmanager:get-php-list', function () {
    // Get www-root settings for fpm version
    $response = ispmanagerRequest('get', [
        'func' => 'user.edit',
        'elid' => get('ispmanager_owner'),
        'elname' => get('ispmanager_owner'),
    ]);

    $userFPMVersion = isset ($response['doc']['limit_php_fpm_version']['$']) ? $response['doc']['limit_php_fpm_version']['$'] : NULL;

    $response = ispmanagerRequest('get', [
        'func' => 'phpversions'
    ]);

    $versions = [];
    foreach ($response['doc']['elem'] as $phpVersion) {
        $versions[$phpVersion['key']['$']] = [
            'name' => $phpVersion['name']['$'],
            'php_mode_mod' => false,
            'php_mode_cgi' => false,
            'php_mode_fcgi_apache' => false,
            'php_mode_fcgi_nginxfpm' => false,
        ];

        if (isset ($phpVersion['default_apache']) && $phpVersion['default_apache']['$'] == 'on') {
            $versions[$phpVersion['key']['$']]['php_mode_mod'] = true;
        }

        if (isset ($phpVersion['cgi']) && $phpVersion['cgi']['$'] == 'on') {
            $versions[$phpVersion['key']['$']]['php_mode_cgi'] = true;
        }

        if (isset ($phpVersion['apache']) && $phpVersion['apache']['$'] == 'on') {
            $versions[$phpVersion['key']['$']]['php_mode_fcgi_apache'] = true;
        }

        if (isset ($phpVersion['fpm']) && $phpVersion['fpm']['$'] == 'on' && $phpVersion['key']['$'] == $userFPMVersion) {
            $versions[$phpVersion['key']['$']]['php_mode_fcgi_nginxfpm'] = true;
        }

    }

    add('ispmanager_phplist', $versions);
});

desc('Prints allowed PHP modes and versions');
task('ispmanager:print-php-list', function () {
    invoke('ispmanager:get-php-list');

    $versions = get('ispmanager_phplist');
    writeln("PHP versions: ");
    writeln(str_repeat('*', 32));
    foreach ($versions as $versionKey => $versionData) {
        writeln('PHP ' . $versionData['name'] . ' (ID: ' . $versionKey . ')');
        writeln(str_repeat('*', 32));
        if (!$versionData['php_mode_mod']) {
            writeln('Apache module support (php_mode_mod) - <fg=red;options=bold>NO</>');
        } else {
            writeln('Apache module support (php_mode_mod) - <fg=green;options=bold>YES</>');
        }

        if (!$versionData['php_mode_cgi']) {
            writeln('CGI support (php_mode_cgi) - <fg=red;options=bold>NO</>');
        } else {
            writeln('CGI support (php_mode_cgi) - <fg=green;options=bold>YES</>');
        }

        if (!$versionData['php_mode_fcgi_apache']) {
            writeln('Apache fast-cgi support (php_mode_fcgi_apache) - <fg=red;options=bold>NO</>');
        } else {
            writeln('Apache fast-cgi support (php_mode_fcgi_apache) - <fg=green;options=bold>YES</>');
        }

        if (!$versionData['php_mode_fcgi_nginxfpm']) {
            writeln('nginx fast-cgi support (php_mode_fcgi_nginxfpm) - <fg=red;options=bold>NO</>');
        } else {
            writeln('nginx fast-cgi support (php_mode_fcgi_nginxfpm) - <fg=green;options=bold>YES</>');
        }

        writeln(str_repeat('*', 32));
    }
});

desc('Switches PHP version for domain');
task('ispmanager:domain-php-select', function () {
    $config = get('ispmanager');

    if (is_null($config['phpSelect'])) {
        warning('Action for domain update is not active');
        return;
    }

    if (!isset ($config['phpSelect']['name']) || $config['phpSelect']['name'] == '') {
        throw new Exception('Invalid domain name!');
    }

    $existDomains = get('ispmanager_domains');

    if (!in_array($config['phpSelect']['name'], $existDomains)) {
        throw new Exception('Domain not exist!');
    }

    if (!isset ($config['phpSelect']['mode']) || !isset ($config['phpSelect']['version'])) {
        throw new Exception('Incorrect settings for select php version');
    }

    $phpVersions = get('ispmanager_phplist');

    $newVersion = $config['phpSelect']['version'];
    $newMode = $config['phpSelect']['mode'];

    if (!isset ($phpVersions[$newVersion])) {
        throw new Exception('Incorrect php version');
    }

    $versionData = $phpVersions[$newVersion];

    if (!isset ($versionData[$newMode]) || !$versionData[$newMode]) {
        throw new Exception('Incorrect php mode');
    }

    $domainUpdateRequest = [
        'func' => 'webdomain.edit',
        'elid' => $config['phpSelect']['name'],
        'name' => $config['phpSelect']['name'],
        'php_mode' => $newMode,
        'sok' => 'ok',
    ];

    if ($newMode == 'php_mode_mod') {
        $domainUpdateRequest['php_apache_version'] = $newVersion;
    } elseif ($newMode == 'php_mode_cgi') {
        $domainUpdateRequest['php_cgi_version'] = $newVersion;
    } elseif ($newMode == 'php_mode_fcgi_apache') {
        $domainUpdateRequest['php_cgi_version'] = $newVersion;
        $domainUpdateRequest['php_apache_version'] = $newVersion;
    } elseif ($newMode == 'php_mode_fcgi_nginxfpm') {
        $domainUpdateRequest['php_cgi_version'] = $newVersion;
        $domainUpdateRequest['php_fpm_version'] = $newVersion;
    } else {
        throw new Exception('Unknown PHP mode!');
    }

    $response = ispmanagerRequest('post', $domainUpdateRequest);

    if (isset ($response['doc']['error']['msg']['$'])) {
        throw new Exception($response['doc']['error']['msg']['$']);
    } else {
        info('PHP successfully selected');
    }
});

desc('Creates new domain alias');
task('ispmanager:domain-alias-create', function () {
    $config = get('ispmanager');

    if (is_null($config['createAlias'])) {
        warning('Action for alias create is not active');
        return;
    }

    if (!isset ($config['createAlias']['name']) || $config['createAlias']['name'] == '') {
        throw new Exception('Invalid domain name!');
    }

    $existDomains = get('ispmanager_domains');

    if (!in_array($config['createAlias']['name'], $existDomains)) {
        throw new Exception('Domain not exist!');
    }

    if (!isset ($config['createAlias']['alias']) || $config['createAlias']['alias'] == '') {
        throw new Exception('Invalid alias name!');
    }

    // Get current domain data
    $response = ispmanagerRequest('get', [
        'func' => 'webdomain.edit',
        'elid' => $config['createAlias']['name'],
        'elname' => $config['createAlias']['name'],
    ]);

    $existAliases = [];
    if (isset ($response['doc']['aliases']['$'])) {
        $existAliases = explode(' ', $response['doc']['aliases']['$']);
    }

    $newAliasList = [];
    $createAliasList = explode(' ', $config['createAlias']['alias']);
    foreach ($createAliasList as $createAlias) {
        if (in_array($createAlias, $existAliases)) {
            if (!isset ($config['createAlias']['skipIfExist']) || !$config['createAlias']['skipIfExist']) {
                throw new Exception('Alias already exists!');
            } else {
                warning('Alias ' . $createAlias . ' already exists - skipping');
                continue;
            }
        }

        $newAliasList[] = $createAlias;
    }

    $saveAliases = array_merge($existAliases, $newAliasList);

    $domainUpdateRequest = [
        'func' => 'webdomain.edit',
        'elid' => $config['createAlias']['name'],
        'name' => $config['createAlias']['name'],
        'aliases' => implode(' ', $saveAliases),
        'sok' => 'ok',
    ];

    $response = ispmanagerRequest('post', $domainUpdateRequest);

    if (isset ($response['doc']['error']['msg']['$'])) {
        throw new Exception($response['doc']['error']['msg']['$']);
    } else {
        info('Alias successfully created');
    }
});

desc('Deletes domain alias');
task('ispmanager:domain-alias-delete', function () {
    $config = get('ispmanager');

    if (is_null($config['deleteAlias'])) {
        warning('Action for alias create is not active');
        return;
    }

    if (!isset ($config['deleteAlias']['name']) || $config['deleteAlias']['name'] == '') {
        throw new Exception('Invalid domain name!');
    }

    $existDomains = get('ispmanager_domains');

    if (!in_array($config['deleteAlias']['name'], $existDomains)) {
        throw new Exception('Domain not exist!');
    }

    if (!isset ($config['deleteAlias']['alias']) || $config['deleteAlias']['alias'] == '') {
        throw new Exception('Invalid alias name!');
    }

    // Get current domain data
    $response = ispmanagerRequest('get', [
        'func' => 'webdomain.edit',
        'elid' => $config['createAlias']['name'],
        'elname' => $config['createAlias']['name'],
    ]);

    $existAliases = [];
    if (isset ($response['doc']['aliases']['$'])) {
        $existAliases = explode(' ', $response['doc']['aliases']['$']);
    }

    $deleteAliasList = explode(' ', $config['deleteAlias']['alias']);
    foreach ($deleteAliasList as $deleteAlias) {
        if (!in_array($deleteAlias, $existAliases)) {
            if (!isset ($config['deleteAlias']['skipIfNotExist']) || !$config['deleteAlias']['skipIfNotExist']) {
                throw new Exception('Alias not exist!');
            } else {
                warning('Alias ' . $deleteAlias . ' not exist - skipping');
                continue;
            }
        }

        if (($index = array_search($deleteAlias, $existAliases)) !== FALSE) {
            unset ($existAliases[$index]);
        }
    }

    $domainUpdateRequest = [
        'func' => 'webdomain.edit',
        'elid' => $config['deleteAlias']['name'],
        'name' => $config['deleteAlias']['name'],
        'aliases' => implode(' ', $existAliases),
        'sok' => 'ok',
    ];

    $response = ispmanagerRequest('post', $domainUpdateRequest);

    if (isset ($response['doc']['error']['msg']['$'])) {
        throw new Exception($response['doc']['error']['msg']['$']);
    } else {
        info('Alias successfully deleted');
    }
});

desc('Deletes domain');
task('ispmanager:domain-delete', function () {
    $config = get('ispmanager');

    if (is_null($config['deleteDomain'])) {
        warning('Action for domain delete is not active');
        return;
    }

    if (!isset ($config['deleteDomain']['name']) || $config['deleteDomain']['name'] == '') {
        throw new Exception('Invalid domain name!');
    }

    // Check domain exists
    $existDomains = get('ispmanager_domains');

    if (!in_array($config['deleteDomain']['name'], $existDomains)) {
        if (!isset ($config['deleteDomain']['skipIfNotExist']) || !$config['deleteDomain']['skipIfNotExist']) {
            throw new Exception('Domain not exist!');
        } else {
            warning('Domain not exist - skipping');
            return;
        }
    }

    // Build request
    $domainDeleteRequest = [
        'func' => 'webdomain.delete.confirm',
        'elid' => $config['deleteDomain']['name'],
        'sok' => 'ok',
    ];

    if (!isset ($config['deleteDomain']['removeDir']) || !$config['deleteDomain']['removeDir']) {
        $domainDeleteRequest['remove_directory'] = 'off';
    } else {
        $domainDeleteRequest['remove_directory'] = 'on';
    }

    $response = ispmanagerRequest('post', $domainDeleteRequest);

    if (isset ($response['doc']['error']['msg']['$'])) {
        throw new Exception($response['doc']['error']['msg']['$']);
    } else {
        info('Domain successfully deleted');
    }
});

desc('Auto task processing');
task('ispmanager:process', function () {
    $config = get('ispmanager');

    if (!is_null($config['createDatabase'])) {
        invoke('ispmanager:db-create');
    }

    if (!is_null($config['deleteDatabase'])) {
        invoke('ispmanager:db-delete');
    }

    if (!is_null($config['createDomain'])) {
        invoke('ispmanager:domain-create');
    }

    if (!is_null($config['deleteDomain'])) {
        invoke('ispmanager:domain-delete');
    }

    if (!is_null($config['phpSelect'])) {
        invoke('ispmanager:domain-php-select');
    }

    if (!is_null($config['createAlias'])) {
        invoke('ispmanager:domain-alias-create');
    }

    if (!is_null($config['deleteAlias'])) {
        invoke('ispmanager:domain-alias-delete');
    }
});

function ispmanagerRequest($method, $requestData)
{
    $config = get('ispmanager');
    $dsnData = parse_url($config['api']['dsn']);

    $requestUrl = $dsnData['scheme'] . '://' . $dsnData['host'] . ':' . $dsnData['port'] . $dsnData['path'];

    if ($config['api']['secure'] && get('ispmanager_session') == '') {
        ispmanagerAuthRequest($requestUrl, $dsnData['user'], $dsnData['pass']);
    }

    if ($method == 'post') {
        return Httpie::post($requestUrl)
            ->formBody(prepareRequest($requestData))
            ->setopt(CURLOPT_SSL_VERIFYPEER, false)
            ->setopt(CURLOPT_SSL_VERIFYHOST, false)
            ->getJson();
    } elseif ($method == 'get') {
        return Httpie::get($requestUrl)
            ->query(prepareRequest($requestData))
            ->setopt(CURLOPT_SSL_VERIFYPEER, false)
            ->setopt(CURLOPT_SSL_VERIFYHOST, false)
            ->getJson();
    } else {
        throw new Exception('Unknown request method');
    }
}

function ispmanagerAuthRequest($url, $login, $pass)
{
    $authRequestData = [
        'func' => 'auth',
        'username' => $login,
        'password' => $pass,
    ];

    $responseData = Httpie::post($url)
        ->setopt(CURLOPT_SSL_VERIFYPEER, false)
        ->setopt(CURLOPT_SSL_VERIFYHOST, false)
        ->formBody(prepareRequest($authRequestData))
        ->getJson();

    if (isset ($responseData['doc']['auth']['$id'])) {
        set('ispmanager_session', $responseData['doc']['auth']['$id']);
    } else {
        throw new Exception('Error while create auth session');
    }
}

function prepareRequest($requestData)
{
    $config = get('ispmanager');
    $dsnData = parse_url($config['api']['dsn']);

    if (!isset ($requestData['out'])) {
        $requestData['out'] = 'json';
    }

    if (!$config['api']['secure']) {
        $requestData['authinfo'] = $dsnData['user'] . ':' . $dsnData['pass'];
    } else {
        if (get('ispmanager_session') != '') {
            $requestData['auth'] = get('ispmanager_session');
        }
    }

    return $requestData;
}

function generatePassword($lenght)
{
    return substr(md5(uniqid()), 0, $lenght);
}

// Callbacks before actions under domains
before('ispmanager:domain-alias-create', 'ispmanager:init');
before('ispmanager:domain-alias-delete', 'ispmanager:init');
before('ispmanager:domain-create', 'ispmanager:init');
before('ispmanager:domain-delete', 'ispmanager:init');
before('ispmanager:domain-php-select', 'ispmanager:init');

// Callbacks before actions under databases
before('ispmanager:db-create', 'ispmanager:init');
before('ispmanager:db-delete', 'ispmanager:init');
