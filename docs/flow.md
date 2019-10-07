# Flow

If your recipe is based on the *common* recipe or one of the framework recipes shipped with Deployer, then you are using one of our default flows.
Each flow is described as a group of other tasks in the `deploy` name space. A common deploy flow may look like this:

~~~php
task('deploy', [
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
    'deploy:vendors',
    'deploy:clear_paths',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
    'success'
]);
~~~

Framework recipes may differ in flow, but the basic structure is the same. You can create your own flow by overriding the `deploy` task, but a better solution is to use the cache. 
For example, if you want to run some task before you symlink the new release:

~~~php
before('deploy:symlink', 'deploy:build');
~~~

Or, to send notifications after a successful deployment:

~~~php
after('success', 'notify');
~~~

The next section provides a short overview of each task. 

### deploy:prepare

Preparation for deployment. Checks if `deploy_path` exists, otherwise creates it. Also checks for the existence of the following paths:

* `releases` – the releases will be stored in this dir.
* `shared` – shared files across all releases will be stored in this dir.
* `.dep` – dir with metadata used by Deployer.

### deploy:lock

Locks deployment so only one concurrent deployment can be running. To lock deployment, this task checks for the existence of the  `.dep/deploy.lock` file. If the deploy process was cancelled by Ctrl+C, run `dep deploy:unlock` to delete this file. In the event that deployment fails, the `deploy:unlock` task will be triggered automatically. 

### deploy:release

Creates a new release folder based on the `release_name` config parameter. Also reads `.dep/releases` to get a list of releases that were created before. 

Also, if in the `deploy_path` there was a previous release symlink, it will be deleted.

### deploy:update_code

Downloads a new version of code using Git. If you are using Git version 2.0 and `git_cache` config is turned on, this task will use files from the previous release, so only changed files will be downloaded.

Override this task in `deploy.php` to create your own code transfer strategy:

~~~php
task('deploy:update_code', function () {
    upload('.', '{{release_path}}');
});
~~~

### deploy:shared

Creates shared files and directories from the `shared` directory into the `release_path`. You can specify shared directories and files in `shared_dirs` and `shared_files` config parameters. The process is split into the following steps:

* Copy dir from `release_path` to `shared` if doesn't exist,
* delete dir from `release_path`,
* symlink dir from `shared` to `release_path`.

The same steps are followed for shared files. If your system supports relative symlinks then they will be used, otherwise absolute symlinks wil be used.

### deploy:writable

Makes the directories listed in `writable_dirs` writable using `acl` mode (using `setfacl` command) by default. This task will try to guess http_user name, or you can configure it yourself:

~~~php
set('http_user', 'www-data');

// Or only for specified host:
host(...)
    ->set('http_user', 'www-data');
~~~

Also this task supports other writable modes:

* chown
* chgrp
* chmod
* acl

To use one of them add this:

~~~php
set('writable_mode', 'chmod');
~~~

To use sudo with writable add this:

~~~php
set('writable_use_sudo', true);
~~~

### deploy:vendors

Installs composer dependencies. You can configure composer options with the `composer_options` parameter. 

### deploy:clear_paths

Deletes dirs specified in `clear_paths`. This task can be run with sudo using the `clear_use_sudo` parameter.

### deploy:symlink

Switches the `current` symlink to `release_path`. If target system supports atomic switching for symlinks it will used.

### deploy:unlock

Deletes the `.dep/deploy.lock` file. You can run this task directly to delete the lock file:

~~~sh
dep deploy:unlock staging
~~~

### cleanup

Cleans up old releases using the `keep_releases` option. `-1` is treated as unlimited releases.

### success

Prints a success message.
