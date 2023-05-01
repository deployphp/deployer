# Upgrade a major version

## Upgrade from 6.x to 7.x

### Step 1: Update deploy.php

1. Change config `hostname` to `alias`.
2. Change config `real_hostname` to `hostname`.
3. Change config `user` to `remote_user`.
4. Update `host()` definitions:
   1. Add `set` prefix to all setters: `identityFile` -> `setIdentityFile` or `set('identity_file')`
   2. Update `host(...)->addSshOption('UserKnownHostsFile', '/dev/null')` to `host(...)->setSshArguments(['-o UserKnownHostsFile=/dev/null']);`
   3. Replace _stage_ with labels, i.e.
      ```php
      host('deployer.org')
          ->set('labels', ['stage' => 'prod']);
      ```
      When deploying instead of `dep deploy prod` use `dep deploy stage=prod`.
   4. `alias()` is deleted, `host()` itself sets alias and hostname, to override hostname use `setHostname()`.
5. Update `task()` definitions.
   1. Replace `onRoles()` with `select()`:
      ```php
      task(...)
          ->select('stage=prod');
      ```
   2. Don't use string-based task definition, it's not available anymore. Don't forget to set correct working directory.
      ```php
      # from
      task('deploy:npm-install', 'npm clean-install');
      
      # to
      task('deploy:npm-install', function() {
          cd('{{release_path}}');
          run('npm clean-install');
      });
      ```
6. Third party recipes now live inside main Deployer repo in _contrib_:
   ```php
   require 'contrib/rsync.php';
   ```
7. Replace `inventory()` with `import()`. It now can import hosts, configs, tasks:

   ```yaml
   import: recipe/common.php

   config:
     application: deployer
     shared_dirs:
       - uploads
       - storage/logs/
       - storage/db
     shared_files:
       - .env
       - config/test.yaml
     keep_releases: 3
     http_user: false

   hosts:
     prod:
       local: true

   tasks:
     deploy:
       - deploy:prepare
       - deploy:vendors
       - deploy:publish

     deploy:vendors:
       - run: "cd {{release_path}} && echo {{bin/composer}} {{composer_options}} 2>&1"
   ```

8. Rename task `success` to `deploy:success` and `cleanup` to `deploy:cleanup`.
9. Verbosity functions (`isDebug()`, etc) got deleted. Use `output()->isDebug()` instead.
10. `runLocally()` commands are executed relative to the recipe file directory. This behaviour can be overridden via an environment variable:
    ```
    DEPLOYER_ROOT=. vendor/bin/dep taskname`
    ```
11. Replace `local()` tasks with combination of `once()` and `runLocally()` func.
12. Replace `locateBinaryPath()` with `which()` func.
13. Configuration property `default_stage` is not supported anymore and has been dropped.
14. Replace `onHosts()` and `onStage()` with [labels & selectors](selector.md).
15. Replace `setPrivate()` with [`hidden()`](tasks.md#hidden).
16. Configuration property `writable_recursive` defaults to `false`. This behaviour can be overridden with:
   ```php
   set('writable_recursive', true);
   ```
17. `.git` directory is not present in release directory anymore. The previous behavior can be restored with:
   ```php
   set('update_code_strategy', 'clone');
   ```

### Step 2: Deploy

Since the release history numbering is not compatible between v6 and v7, you need to specify the `release_name` manually for the first time. Otherwise you start with release 1.

1. Find out next release name (ssh to the host, `ls` releases dir, find the biggest number). Example: `42`.
2. Deploy with release_name:
   ```
   dep deploy -o release_name=43
   ```

:::note
In case a rollback is needed, manually change the `current` symlink:

```
ln -nfs releases/42 current
```

:::

:::note
In case there are multiple hosts with different release names, you should create a `{{deploy_path}}/.dep/latest_release` file in each host with the current release number of that particular host.
:::

## Upgrade from 5.x to 6.x

1. Changed branch option priority

   If you have host definition with `branch(...)` parameter, adding `--branch` option will not override it any more.
   If no `branch(...)` parameter persists, branch will be fetched from current local git branch.

   ```php
   host('prod')
       ->set('branch', 'production')
   ```

   In order to return to old behavior add checking of `--branch` option.

   ```php
   host('prod')
       ->set('branch', function () {
           return input()->getOption('branch') ?: 'production';
       })
   ```

2. Add `deploy:info` task to the beginning to `deploy` task.
3. `run` returns string instead of `Deployer\Type\Result`

   Now `run` and `runLocally` returns `string` instead of `Deployer\Type\Result`.
   Replace method calls as:

   - `run('command')->toString()` → `run('command')`
   - `run('if command; then echo "true"; fi;')->toBool()` → `test('command')`

4. `env_vars` renamed to `env`

   - `set('env_vars', 'FOO=bar');` → `set('env', ['FOO' => 'bar']);`

   If your are using Symfony recipe, then you need to change `env` setting:

   - `set('env', 'prod');` → `set('symfony_env', 'prod');`

## Upgrade from 4.x to 5.x

1. Servers to Hosts

   - `server($hostname)` to `host($hostname)`, and `server($name, $hostname)` to `host($name)->hostname($hostname)`
   - `localServer($name)` to `localhost()`
   - `cluster($name, $nodes, $port)` to `hosts(...$hodes)`
   - `serverList($file)` to `inventory($file)`

   If you need to deploy to same server use [host aliases](https://deployer.org/docs/hosts#host-aliases):

   ```php
   host('domain.com/green', 'domain.com/blue')
       ->set('deploy_path', '~/{{hostname}}')
       ...
   ```

   Or you can define different hosts with same hostname:

   ```php
   host('production')
       ->hostname('domain.com')
       ->set('deploy_path', '~/production')
       ...

   host('beta')
       ->hostname('domain.com')
       ->set('deploy_path', '~/beta')
       ...
   ```

2. Configuration options

   - Rename `{{server.name}}` to `{{hostname}}`

3. DotArray syntax

   In v5 access to nested arrays in config via dot notation was removed.
   If you was using it, consider to move to plain config options.

   Refactor this:

   ```php
   set('a', ['b' => 1]);

   // ...

   get('a.b');
   ```

   To:

   ```php
   set('a_b', 1);

   // ...

   get('a_b');
   ```

4. Credentials

   Best practice in new v5 is to omit credentials for connection in `deploy.php` and write them in `~/.ssh/config` instead.

   - `identityFile($publicKeyFile,, $privateKeyFile, $passPhrase)` to `identityFile($privateKeyFile)`
   - `pemFile($pemFile)` to `identityFile($pemFile)`
   - `forwardAgent()` to `forwardAgent(true)`

5. Tasks constraints

   - `onlyOn` to `onHosts`
   - `onlyOnStage` to `onStage`

## Upgrade from 3.x to 4.x

1. Namespace for functions

   Add to beginning of _deploy.php_ next line:

   ```php
   use function Deployer\{server, task, run, set, get, add, before, after};
   ```

   If you are using PHP version less than 5.6, you can use this:

   ```php
   namespace Deployer;
   ```

2. `env()` to `set()`/`get()`

   Rename all calls `env($name, $value)` to `set($name, $value)`.

   Rename all rvalue `env($name)` to `get($name)`.

   Rename all `server(...)->env(...)` to `server(...)->set(...)`.

3. Moved _NonFatalException_

   Rename `Deployer\Task\NonFatalException` to `Deployer\Exception\NonFatalException`.

4. Prior release cleanup

   Due to changes in release management, the new cleanup task will ignore any prior releases deployed with 3.x. These will need to be manually removed after migrating to and successfully releasing via 4.x.

## Upgrade from 2.x to 3.x

1. ### `->path('...')`

   Replace your server paths configuration:

   ```php
   server(...)
     ->path(...);
   ```

   to:

   ```php
   server(...)
     ->env('deploy_path', '...');
   ```
