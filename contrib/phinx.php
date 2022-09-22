<?php
/*

## Configuration options

All options are in the config parameter `phinx` specified as an array (instead of the `phinx_path` variable).
All parameters are *optional*, but you can specify them with a dictionary (to change all parameters)
or by deployer dot notation (to change one option).

### Phinx params

- `phinx.environment`
- `phinx.date`
- `phinx.configuration` N.B. current directory is the project directory
- `phinx.target`
- `phinx.seed`
- `phinx.parser`
- `phinx.remove-all` (pass empty string as value)

### Phinx path params

- `phinx_path` Specify phinx path (by default phinx is searched for in $PATH, ./vendor/bin and ~/.composer/vendor/bin)

### Example of usage

```php
$phinx_env_vars = [
  'environment' => 'development',
  'configuration' => './migration/.phinx.yml',
  'target' => '20120103083322',
  'remove-all' => '',
];

set('phinx_path', '/usr/local/phinx/bin/phinx');
set('phinx', $phinx_env_vars);

after('cleanup', 'phinx:migrate');

// or set it for a specific server
host('dev')
    ->user('user')
    ->set('deploy_path', '/var/www')
    ->set('phinx', $phinx_env_vars)
    ->set('phinx_path', '');
```

## Suggested Usage

You can run all tasks before or after any
tasks (but you need to specify external configs for phinx).
If you use internal configs (which are in your project) you need
to run it after the `deploy:update_code` task is completed.

## Read more

For further reading see [phinx.org](https://phinx.org). Complete descriptions of all possible options can be found on the [commands page](http://docs.phinx.org/en/latest/commands.html).

 */
namespace Deployer;

use Deployer\Exception\RunException;

/*
 * Phinx recipe for Deployer
 *
 * @author    Alexey Boyko <ket4yiit@gmail.com>
 * @contributor Security-Database <info@security-database.com>
 * @copyright 2016 Alexey Boyko
 * @license   MIT https://github.com/deployphp/recipes/blob/master/LICENSE
 *
 * @link https://github.com/deployphp/recipes
 *
 * @see http://deployer.org
 * @see https://phinx.org
 */

/**
 * Path to Phinx
 */
set('bin/phinx', function () {
    try {
        $phinxPath = which('phinx');
    } catch (RunException $e) {
        $phinxPath = null;
    }

    if ($phinxPath !== null) {
        return "phinx";
    } else if (test('[ -f {{release_path}}/vendor/bin/phinx ]')) {
        return "{{release_path}}/vendor/bin/phinx";
    } else if (test('[ -f ~/.composer/vendor/bin/phinx ]')) {
        return '~/.composer/vendor/bin/phinx';
    } else {
        throw new \RuntimeException('Cannot find phinx. Please specify path to phinx manually');
    }
});

/**
 * Make Phinx command
 *
 * @param string $cmdName Name of command
 * @param array $conf Command options(config)
 *
 * @return string Phinx command to execute
 */
function phinx_get_cmd($cmdName, $conf)
{
    $phinx = get('phinx_path') ?: get('bin/phinx');

    $phinxCmd = "$phinx $cmdName";

    $options = '';

    foreach ($conf as $name => $value) {
        $options .= " --$name $value";
    }

    $phinxCmd .= $options;

    return $phinxCmd;
}

/**
 * Returns options array that allowed for command
 *
 * @param array $allowedOptions List of allowed options
 *
 * @return array Array of options
 */
function phinx_get_allowed_config($allowedOptions)
{
    $opts = [];

    try {
        foreach (get('phinx') as $key => $val) {
            if (in_array($key, $allowedOptions)) {
                $opts[$key] = $val;
            }
        }
    } catch (\RuntimeException $e) {
    }

    return $opts;
}


desc('Migrats database with phinx');
task('phinx:migrate', function () {
    $ALLOWED_OPTIONS = [
        'configuration',
        'date',
        'environment',
        'target',
        'parser'
    ];

    $conf = phinx_get_allowed_config($ALLOWED_OPTIONS);

    cd('{{release_path}}');

    $phinxCmd = phinx_get_cmd('migrate', $conf);

    run($phinxCmd);

    cd('{{deploy_path}}');
});

desc('Rollbacks database migrations with phinx');
task('phinx:rollback', function () {
    $ALLOWED_OPTIONS = [
        'configuration',
        'date',
        'environment',
        'target',
        'parser'
    ];

    $conf = phinx_get_allowed_config($ALLOWED_OPTIONS);

    cd('{{release_path}}');

    $phinxCmd = phinx_get_cmd('rollback', $conf);

    run($phinxCmd);

    cd('{{deploy_path}}');
});

desc('Seeds database with phinx');
task('phinx:seed', function () {
    $ALLOWED_OPTIONS = [
        'configuration',
        'environment',
        'parser',
        'seed'
    ];

    $conf = phinx_get_allowed_config($ALLOWED_OPTIONS);

    cd('{{release_path}}');

    $phinxCmd = phinx_get_cmd('seed:run', $conf);

    run($phinxCmd);

    cd('{{deploy_path}}');
});

desc('Sets a migrations breakpoint with phinx');
task('phinx:breakpoint', function () {
    $ALLOWED_OPTIONS = [
        'configuration',
        'environment',
        'remove-all',
        'target'
    ];

    $conf = phinx_get_allowed_config($ALLOWED_OPTIONS);

    cd('{{release_path}}');

    $phinxCmd = phinx_get_cmd('breakpoint', $conf);

    run($phinxCmd);

    cd('{{deploy_path}}');
});
