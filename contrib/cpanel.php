<?php

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
    $stage = Context::get()->getInput()->getArgument('stage');

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

desc('Creating database though CPanel API');
task('cpanel:createdb', function () {

    $cpanel = getCPanel();
    $config = get('cpanel', []);
    if (!askConfirmation(sprintf('This will try to create the database %s on the host though CPanel API, ok?', get('cpanel_createdb'), get('deploy_path')), true)) {
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

desc('Creating addon domain though CPanel API');
task('cpanel:createaddondomain', function () {
    $cpanel = getCPanel();
    $config = get('cpanel', []);
    $domain = getDomainInfo()['domain'];
    $subDomain = getDomainInfo()['subDomain'];
    if (!askConfirmation(sprintf('This will try to create the addon domain %s and point it to %s and subdomain %s, ok?', $domain, get('addondir'), $subDomain), true)) {
        return;
    }

    writeln(sprintf('Creating addon domain %s and pointing it to %s', $domain, get('addondir')));

    $addAddonDomainResult = $cpanel->cpanel('AddonDomain', 'addaddondomain', $config['user'], ['dir' => get('addondir'), 'newdomain'=> $domain, 'subdomain' => $subDomain]);
    $addAddonDomainData = json_decode($addAddonDomainResult, true);

    if (isset($delAddonDomainResult['cpanelresult']['error'])) {
        writeln($addAddonDomainData['cpanelresult']['error']);
    } else {
        writeln('Successfully created addon domain!');
        writeln($addAddonDomainData['cpanelresult']['data'][0]['reason']);
    }
});

desc('Delete addon domain though CPanel API');
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
