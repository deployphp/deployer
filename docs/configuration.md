# Configuration

To setup a configuration parameter, use `set` function, and to get it inside task use `get` function.

~~~php
set('param', 'value');

task('deploy', function () {
    $param = get('param');
});
~~~

Each parameter can be overridden for each host:

~~~php
host(...)
    ->set('param', 'new value');
~~~

Configuration parameters also can be specified as callback function, which will be executed on remote host on first `get` call:

~~~php
set('current_path', function () {
    return run('pwd');
});
~~~

You can use a param's values inside `run` calls with `{{ }}`, instead of doing this:

~~~php
run('cd ' . get('release_path') . ' && command');
~~~

You can do this:

~~~php
run('cd {{release_path}} && command');
~~~

Common recipe comes with a few predefined config params listed below. 

To get list of available params run:

~~~sh
dep config:dump
~~~

Show current deployed release:

~~~bash
dep config:current
~~~

Show inventory:

~~~bash
dep config:hosts
~~~



Below is a list of common variables.

### deploy\_path

Where to deploy application on remote host. You should define this variable for all of your hosts.
For example, if you want to deploy your app to home directory:

~~~php
host(...)
    ->set('deploy_path', '~/project');
~~~

### hostname

Current hostname. Automatically set by `host` function.

### user

Current user name. Defaults to the current git user name:

~~~php
set('user', function () {
    return runLocally('git config --get user.name');
});
~~~

You can override it in _deploy.php_ for example to use env var:

~~~php
set('user', function () {
    return getenv('DEP_USER');
});
~~~

`user` parameter can be used to configure notification systems:

~~~php
set('slack_text', '{{user}} deploying {{branch}} to {{hostname}}');
~~~

### release\_path

Full path to the current release directory. Current dir path in non-deploy contexts.
Use it as working path for your build:

~~~php
task('build', function () {
    cd('{{release_path}}');
    // ...
});
~~~

> By default, working path is `release_path` for simple task:
> ~~~php
> task('build', 'webpack -p');
> ~~~

### previous\_release

Points to previous release if it exists. Otherwise variable doesn't exist.

~~~php
task('npm', function () {
    if (has('previous_release')) {
        run('cp -R {{previous_release}}/node_modules {{release_path}}/node_modules');
    }
    
    run('cd {{release_path}} && npm install');
});
~~~

### ssh\_multiplexing

Use [ssh multiplexing](https://en.wikibooks.org/wiki/OpenSSH/Cookbook/Multiplexing) to speedup the native ssh client.

~~~php
set('ssh_multiplexing', true);
~~~

### default\_stage

This option allows you to select the default stage to deploy with `dep deploy`. Note that your Deployer script must have at least one stage and you must specify a stage, even if you are just running [local-only tasks](https://deployer.org/docs/tasks.html#local-tasks). Therefore it is recommended you use this command to always set a default stage.

~~~php
set('default_stage', 'staging');

host(...)
    ->stage('staging');
~~~

You can also set callable as an argument if you need some more complex ways to determine default stage.

Having callable in set() allows you to not set the value when declaring it, but later when it is used. There is no difference 
when we assign a simple string. But when we assign value of a function, then this function must be called at once, if not used 
as callable. With callable, it can be called when used, so a function which determines a variable can be overwritten by the user with its own function. This is the great power of having callable in set() instead of direct in function calls.

**Example 1: Direct function assign in set()**

Lets assume that we must include some third party recipe that is setting 'default_stage' like this:
~~~php
set('default_stage', \ThirdPartyVendor\getDefaultStage());
~~~

And we want to overwrite this in our deploy.php with our own value:
~~~php
set('default_stage', \MyVendor\getDefaultStage());
~~~

Third party recipe should avoid a direct function call, because it will be called always even if we overwrite it with 
our own set('default_stage', \MyVendor\getDefaultStage()). Look at the next example how the third party recipe should use
callable in that case.

**Example 2: Callable assign in set()**

Lets assume that we must include some third party recipe that is setting 'default_stage' like this:
~~~php
set('default_stage', function() {
    return \ThirdPartyVendor\getDefaultStage();
});
~~~

And we want to overwrite this in our deploy.php:
~~~php
set('default_stage', function() {
    return \MyVendor\getDefaultStage();
});
~~~

The result is that only \MyVendor\getDefaultStage() is run.

### keep\_releases

Number of releases to keep. `-1` for unlimited releases. Default to `5`.

### repository

Git repository of the application.

To use a private repository, you need to generate a SSH-key on your host and add it to the repository
as a Deploy Key (a.k.a. Access Key). This key allows your host to pull out the code. Or use can use agent forwarding. 

Note that the first time a host connects, it can ask to add host in `known_hosts` file. The easiest way to do this is
by running `git clone <repo>` on your host and saying `yes` when prompted.

### git\_tty

Allocate TTY for `git clone` command. `false` by default. This allow you to enter a passphrase for keys or add host to `known_hosts`.

~~~php
set('git_tty', true);
~~~

### git\_recursive

Set the `--recursive` flag for git clone. `true` by default. Setting this to `false` will prevent submodules from being cloned as well.

~~~php
set('git_recursive', false);
~~~

### branch

Branch to deploy.

If you want to deploy a specific tag or a revision, you can use `--tag` and `--revision` options while running `dep deploy`. E.g.

~~~bash
dep deploy --tag="v0.1"
dep deploy --revision="5daefb59edbaa75"
~~~

Note that `tag` has higher priority than `branch` and lower than `revision`.

### shared\_dirs

List of shared dirs.

~~~php
set('shared_dirs', [
    'logs',
    'var',
    ...
]);
~~~

### shared\_files

List of shared files.

### copy\_dirs

List of files to copy between release.

### writable\_dirs

List of dirs which must be writable for web server.

### writable\_mode

Writable mode

* `acl` (*default*) use `setfacl` for changing ACL of dirs.
* `chmod` use unix `chmod` command,
* `chown` use unix `chown` command,
* `chgrp` use unix `chgrp` command,

### writable\_use\_sudo

Whether to use `sudo` with writable command. Default to `false`.

### writable\_chmod\_mode

Mode for setting `writable_mode` in `chmod`. Default: `0755`.

### writable\_chmod\_recursive

Whether to set `chmod` on dirs recursively or not. Default: `true`.

### http\_user

User the web server runs as. If this parameter is not configured, deployer try to detect it from the process list. 

### clear\_paths

List of paths which need to be deleted in release after updating code. 

### clear\_use\_sudo

Use or not `sudo` with clear\_paths. Default to `false`.

### cleanup\_use\_sudo

Whether to use `sudo` with `cleanup` task. Default to `false`.

### use\_relative\_symlink

Whether to use relative symlinks. By default deployer will detect if the system supports relative symlinks and use them.

> Relative symlink used by default, if your system supports it.

### use\_atomic\_symlink

Whether to use atomic symlinks. By default deployer will detect if system supports atomic symlinks and use them.

> Atomic symlinking is used by default, if your system supports it.

### composer\_action

Composer action. Default is `install`.

### composer\_options

Options for Composer.

### default_timeout

Will set the default_timeout for `run-commands` (0 = unlimited)
~~~php
set('default_timeout', 360);
~~~

### env

Array of environment variables.

~~~php
set('env', [
    'VARIABLE' => 'value',
]);
~~~


Read more about [task definitions](tasks.md).
