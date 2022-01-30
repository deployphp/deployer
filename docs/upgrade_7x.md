# Upgrade from 6.x to 7.x

## Step 1: Update deploy.php
1. Change config `hostname` to `alias`.
2. Change config `real_hostname` to `hostname`.
3. Change config `user` to `remote_user`.
4. Update `host()` definitions:
    1. Add `set` prefix to all setters: `identityFile` -> `setIdentityFile` or `set('identify_file')`
    2. Update `host(...)->addSshOption('UserKnownHostsFile', '/dev/null')` to `host(...)->setSshArguments(['-o UserKnownHostsFile=/dev/null']);`
    3. Replace _stage_ with labels, i.e.
       ```php
       host('deployer.org')
           ->set('labels', ['stage' => 'prod']); 
       ```
       When deploying use instead of `dep deploy prod` use `dep deploy stage=prod`. 
    4. `alias()` is deleted, `host()` itself sets alias and hostname, to override hostname use `setHostname()`.
5. Update `task()` definitions.
    1. Replace `onRoles()` with `select()`:
       ```php
       task(...)
           ->select('stage=prod');
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
       - run: 'cd {{release_path}} && echo {{bin/composer}} {{composer_options}} 2>&1'
   ``` 
8. Rename task `success` to `deploy:success`.
9. Verbosity function (`isDebug()`, etc) deleted. Use `output()->isDebug()` instead.
10. runLocally() commands are executed relative to the recipe file directory. This behaviour can be overridden via an environment variable:
    ```
    DEPLOYER_ROOT=. vendor/bin/dep taskname`
    ```
11. Replace `local()` tasks with combination of `once()` and `runLocally()` func.
12. Replace `locateBinaryPath()` with `which()` func.

## Step 2: Deploy

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

