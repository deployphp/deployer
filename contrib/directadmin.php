<?php
namespace Deployer;
use Deployer\Task\Context;
use Deployer\Utility\Httpie;

/**
 * getDirectAdminConfig
 *
 * @return array
 */
function getDirectAdminConfig()
{
    $config = get('directadmin', []);

    if (!is_array($config) ||
        !isset($config['host']) ||
        !isset($config['username']) ||
        !isset($config['password']) ) {
        throw new \RuntimeException("Please set the following DirectAdmin config:" . PHP_EOL . "set('directadmin', ['host' => '127.0.0.1', 'port' => 2222, 'username' => 'admin', 'password' => 'password']);");
    }

    return $config;
}

/**
 * DirectAdmin
 *
 * @param string $action
 * @param array $data
 *
 * @return void
 */
function DirectAdmin(string $action, array $data = [])
{
    $config = getDirectAdminConfig();
    $scheme = $config['scheme'] ?? 'http';
    $port = $config['port'] ?? 2222;

    $result = Httpie::post(sprintf('%s://%s:%s/%s', $scheme, $config['host'], $port, $action))
        ->form($data)
        ->setopt(CURLOPT_USERPWD, $config['username'] . ':' . $config['password'])
        ->send();

    parse_str($result, $resultData);

    if ($resultData['error'] === '1') {
        $resultData['details'] = trim($resultData['details']);
        $resultData['details'] = str_replace(['\\n', '\\r'], '', $resultData['details']);
        $resultData['details'] = strip_tags($resultData['details']);

        writeln('<error>DirectAdmin message: ' . $resultData['details'] . '</error>');
    }
}

desc('Create a database on DirectAdmin');
task('directadmin:createdb', function () {
    $config = getDirectAdminConfig();

    if (!is_array($config) ||
        !isset($config['db_name']) ||
        !isset($config['db_user']) ||
        !isset($config['db_password']) ) {
        throw new \RuntimeException("Please add the following DirectAdmin config:" . PHP_EOL . "add('directadmin', ['db_name' => 'test', 'db_user' => 'test', 'db_password' => '123456']);");
    }

    DirectAdmin('CMD_API_DATABASES', [
        'action' => 'create',
        'name' => $config['db_name'],
        'user' => $config['db_user'],
        'passwd' => $config['db_password'],
        'passwd2' => $config['db_password'],
    ]);
});

desc('Delete a database on DirectAdmin');
task('directadmin:deletedb', function () {
    $config = getDirectAdminConfig();

    if (!is_array($config) ||
        !isset($config['db_user'])) {
        throw new \RuntimeException("Please add the following DirectAdmin config:" . PHP_EOL . "add('directadmin', ['db_user' => 'test_database']);");
    }

    DirectAdmin('CMD_API_DATABASES', [
        'action' => 'delete',
        'select0' => $config['username'] . '_' . $config['db_user'],
    ]);
});

desc('Create a domain on DirectAdmin');
task('directadmin:createdomain', function () {
    $config = getDirectAdminConfig();

    if (!is_array($config) ||
        !isset($config['domain_name'])) {
        throw new \RuntimeException("Please add the following DirectAdmin config:" . PHP_EOL . "add('directadmin', ['domain_name' => 'test.example.com']);");
    }

    DirectAdmin('CMD_API_DOMAIN', [
        'action' => 'create',
        'domain' => $config['domain_name'],
        'ssl' => $config['domain_ssl'] ?? 'On',
        'cgi' => $config['domain_cgi'] ?? 'ON',
        'php' => $config['domain_php'] ?? 'ON',
    ]);
});

desc('Delete a domain on DirectAdmin');
task('directadmin:deletedomain', function () {
    $config = getDirectAdminConfig();

    if (!is_array($config) ||
        !isset($config['domain_name'])) {
        throw new \RuntimeException("Please add the following DirectAdmin config:" . PHP_EOL . "add('directadmin', ['domain_name' => 'test.example.com']);");
    }

    DirectAdmin('CMD_API_DOMAIN', [
        'delete' => 'anything',
        'confirmed' => 'anything',
        'select0' => $config['domain_name'],
    ]);
});

desc('Symlink your private_html to public_html');
task('directadmin:symlink-private-html', function () {
    $config = getDirectAdminConfig();

    if (!is_array($config) ||
        !isset($config['domain_name'])) {
        throw new \RuntimeException("Please add the following DirectAdmin config:" . PHP_EOL . "add('directadmin', ['domain_name' => 'test.example.com']);");
    }

    DirectAdmin('CMD_API_DOMAIN', [
        'action' => 'private_html',
        'domain' => $config['domain_name'],
        'val' => 'symlink',
    ]);
});

desc('Change the PHP version from a domain');
task('directadmin:php-version', function () {
    $config = getDirectAdminConfig();

    if (!is_array($config) ||
        !isset($config['domain_name']) ||
        !isset($config['domain_php_version'])) {
        throw new \RuntimeException("Please add the following DirectAdmin config:" . PHP_EOL . "add('directadmin', ['domain_name' => 'test.example.com', 'domain_php_version' => 1]);");
    }

    DirectAdmin('CMD_API_DOMAIN', [
        'action' => 'php_selector',
        'domain' => $config['domain_name'],
        'php1_select' => $config['domain_php_version'],
    ]);
});
