<?php
/*
### Description
This is a recipe that uses the [cPanel 2 API](https://documentation.cPanel.net/display/DD/Guide+to+cPanel+API+2).

Unfortunately the [UAPI](https://documentation.cPanel.net/display/DD/Guide+to+UAPI) that is recommended does not have support for creating addon domains.
The main idea behind is for staging purposes but I guess you can use it for other interesting concepts.

The idea is, every branch possibly has its own staging domain/subdomain (staging-neat-feature.project.com) and database db_neat-feature_project so it can be tested.
This recipe can make the domain/subdomain and database creation part of the deployment process so you don't have to manually create them through an interface.


### Configuration
The example uses a .env file and Dotenv for configuration, but you can set the parameters as you wish
```
set('cpanel', [
    'host' => getenv('CPANEL_HOST'),
    'port' => getenv('CPANEL_PORT'),
    'username' => getenv('CPANEL_USERNAME'),
    'auth_type' => getenv('CPANEL_AUTH_TYPE'),
    'token' => getenv('CPANEL_TOKEN'),
    'user' => getenv('CPANEL_USER'),
    'db_user' => getenv('CPANEL_DB_USER'),
    'db_user_privileges' => getenv('CPANEL_DB_PRIVILEGES'),
    'timeout' => 500,

    'allowInStage' => ['staging', 'beta', 'alpha'],

    'create_domain_format' => '%s-%s-%s',
    'create_domain_values' => ['staging', 'master', get('application')],
    'subdomain_prefix' => substr(md5(get('application')), 0,4) . '-',
    'subdomain_suffix' => getenv('SUDOMAIN_SUFFIX'),


    'create_db_format' => '%s_%s-%s-%s',
    'create_db_values' => ['apps', 'staging','master', get('application')],

]);
```

- `cpanel` – array with configuration for cPanel
    - `username` – WHM account
    - `user` – cPanel account that you want in charge of the domain
    - `token` – WHM API token
    - `create_domain_format` – Format for name creation of domain
    - `create_domain_values` – The actual value reference for naming
    - `subdomain_prefix` – cPanel has a weird way of dealing with addons and subdomains, you cannot create 2 addons with the same subdomain, so you need to change it in some way, example uses first 4 chars of md5(app_name)
    - `subdomain_suffix` – cPanel has a weird way of dealing with addons and subdomains, so the suffix needs to be your main domain for that account for deletion purposes
    - `addondir` – addon dir is different from the deploy path because cPanel "injects" /home/user/ into the path, so tilde cannot be used
    - `allowInStage` – Define the stages that cPanel recipe actions are allowed in


#### .env file example
```
CPANEL_HOST=xxx.xxx.xxx.xxx
CPANEL_PORT=2087
CPANEL_USERNAME=root
CPANEL_TOKEN=xxxx
CPANEL_USER=xxx
CPANEL_AUTH_TYPE=hash
CPANEL_DB_USER=db_user
CPANEL_DB_PRIVILEGES="ALL PRIVILEGES"
SUDOMAIN_SUFFIX=.mymaindomain.com

```

### Tasks

- `cpanel:createaddondomain` Creates an addon domain
- `cpanel:deleteaddondomain` Removes an addon domain
- `cpanel:createdb` Creates a new database

### Usage

A complete example with configs, staging and deployment

```
<?php

namespace Deployer;
use Dotenv\Dotenv;

require 'vendor/autoload.php';
(Dotenv::create(__DIR__))->load(); // this is used just so an .env file can be used for credentials

require 'cpanel.php';


// Project name
set('application', 'myproject.com');
// Project repository
set('repository', 'git@github.com:myorg/myproject.com');





set('cpanel', [
    'host' => getenv('CPANEL_HOST'),
    'port' => getenv('CPANEL_PORT'),
    'username' => getenv('CPANEL_USERNAME'),
    'auth_type' => getenv('CPANEL_AUTH_TYPE'),
    'token' => getenv('CPANEL_TOKEN'),
    'user' => getenv('CPANEL_USER'),
    'db_user' => getenv('CPANEL_DB_USER'),
    'db_user_privileges' => getenv('CPANEL_DB_PRIVILEGES'),
    'timeout' => 500,
    'allowInStage' => ['staging', 'beta', 'alpha'],

    'create_domain_format' => '%s-%s-%s',
    'create_domain_values' => ['staging', 'master', get('application')],
    'subdomain_prefix' => substr(md5(get('application')), 0,4) . '-',
    'subdomain_suffix' => getenv('SUDOMAIN_SUFFIX'),


    'create_db_format' => '%s_%s-%s-%s',
    'create_db_values' => ['apps', 'staging','master', get('application')],

]);

host('myproject.com')
    ->stage('staging')
    ->set('cpanel_createdb', vsprintf(get('cpanel')['create_db_format'], get('cpanel')['create_db_values']))
    ->set('branch', 'dev-branch')
    ->set('deploy_path',  '~/staging/' . vsprintf(get('cpanel')['create_domain_format'], get('cpanel')['create_domain_values']))
    ->set('addondir',  'staging/' . vsprintf(get('cpanel')['create_domain_format'], get('cpanel')['create_domain_values']));
// Tasks
task('build', function () {
    run('cd {{release_path}} && build');
});

after('deploy:prepare', 'cpanel:createaddondomain');
after('deploy:prepare', 'cpanel:createdb');
```
 */
namespace Deployer;

use Deployer\Task\Context;
use \Gufy\CpanelPhp\Cpanel;

/**
 * @return Cpanel
 * @throws Exception\Exception
 */
function getCpanel()
{
    $config = get('cpanel', []);
    $allowInStage = $config['allowInStage'];
    $stage = input()->getArgument('stage');

    if (!class_exists('\Gufy\CpanelPhp\Cpanel')) {
        throw new \RuntimeException("<comment>Please install php package</comment> <info>gufy/cpanel-php</info> <comment>to use CPanel API</comment>");
    }

    if (!in_array($stage, $allowInStage)) {
        throw new \RuntimeException(sprintf("Since it creates addon domains and databases, CPanel recipe is available only in the %s environments", implode($allowInStage)));
    }


    if (!is_array($config) ||
        !isset($config['host']) ||
        !isset($config['port']) ||
        !isset($config['username']) ||
        !isset($config['token']) ||
        !isset($config['user']) ) {
        throw new \RuntimeException("<comment>Please configure CPanel config:</comment> <info>set('cpanel', array('host' => 'xxx.xxx.xxx.xxx:', 'port' => 2087 , 'username' => 'root', 'token' => 'asdfasdf', 'cpaneluser' => 'guy'));</info>");
    }

    $cpanel = new Cpanel([
        'host'        =>  'https://' . $config['host'] . ':' . $config['port'],
        'username'    =>  $config['username'],
        'auth_type'   =>  $config['auth_type'],
        'password'    =>  $config['token'],
    ]);

    $cpanel->setTimeout($config['timeout']);

    return $cpanel;
}

function getDomainInfo()
{
    $domain = vsprintf(get('cpanel')['create_domain_format'], get('cpanel')['create_domain_values']);
    $cleanDomain = str_replace(['.', ',', ' ', '/', '-'], '', $domain);
    $subDomain = get('cpanel')['subdomain_prefix'] . $cleanDomain;

    return [
        'domain' => $domain,
        'subDomain' => $subDomain,
        'subDomainWithSuffix' => $subDomain . get('cpanel')['subdomain_suffix']
    ];
}

desc('Creates database though CPanel API');
task('cpanel:createdb', function () {

    $cpanel = getCPanel();
    $config = get('cpanel', []);
    if (!askConfirmation(sprintf('This will try to create the database %s on the host though CPanel API, ok?', get('cpanel_createdb')), true)) {
        return;
    }

    $createDbDataResult = $cpanel->cpanel('MysqlFE', 'createdb', $config['user'], ['db' => get('cpanel_createdb')]);
    $addPrivilegesDataResult = $cpanel->cpanel('MysqlFE', 'setdbuserprivileges', $config['user'], ['privileges' => $config['db_user_privileges'], 'db'=> get('cpanel_createdb'), 'dbuser' => $config['db_user']]);

    $createDbData = json_decode($createDbDataResult, true);
    $addPrivilegesData = json_decode($addPrivilegesDataResult, true);

    if (isset($createDbData['cpanelresult']['error'])) {
        writeln($createDbData['cpanelresult']['error']);
    } else {
        writeln('Successfully created database!');
    }

    if (isset($addPrivilegesData['cpanelresult']['error'])) {
        writeln($addPrivilegesData['cpanelresult']['error']);
    } else {
        writeln('Successfully added privileges to database!');
    }
});

desc('Creates addon domain though CPanel API');
task('cpanel:createaddondomain', function () {
    $cpanel = getCPanel();
    $config = get('cpanel', []);
    $domain = getDomainInfo()['domain'];
    $subDomain = getDomainInfo()['subDomain'];
    if (!askConfirmation(sprintf('This will try to create the addon domain %s and point it to %s and subdomain %s, ok?', $domain, get('addondir'), $subDomain), true)) {
        return;
    }

    writeln(sprintf('Creates addon domain %s and pointing it to %s', $domain, get('addondir')));

    $addAddonDomainResult = $cpanel->cpanel('AddonDomain', 'addaddondomain', $config['user'], ['dir' => get('addondir'), 'newdomain'=> $domain, 'subdomain' => $subDomain]);
    $addAddonDomainData = json_decode($addAddonDomainResult, true);

    if (isset($addAddonDomainResult['cpanelresult']['error'])) {
        writeln($addAddonDomainData['cpanelresult']['error']);
    } else {
        writeln('Successfully created addon domain!');
        writeln($addAddonDomainData['cpanelresult']['data'][0]['reason']);
    }
});

desc('Deletes addon domain though CPanel API');
task('cpanel:deleteaddondomain', function () {
    $cpanel = getCPanel();
    $config = get('cpanel', []);
    $domain = getDomainInfo()['domain'];
    $subDomain = getDomainInfo()['subDomain'];
    $subDomainWithSuffix = getDomainInfo()['subDomainWithSuffix'];

    if (!askConfirmation(sprintf('This will delete the addon domain %s with corresponding subdomain %s, ok?', $domain, $subDomain), true)) {
        return;
    }

    writeln(sprintf('Deleting addon domain %s', $domain));

    $delAddonDomainResult = $cpanel->cpanel('AddonDomain', 'deladdondomain', $config['user'], ['domain'=> $domain, 'subdomain' => $subDomainWithSuffix]);
    $delAddonDomainResult = json_decode($delAddonDomainResult, true);

    if (isset($delAddonDomainResult['cpanelresult']['error'])) {
        writeln($delAddonDomainResult['cpanelresult']['error']);
    } else {
        writeln('Successfully deleted addon domain!');
        writeln($delAddonDomainResult['cpanelresult']['data'][0]['reason']);
    }
});
