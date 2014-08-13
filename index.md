---
layout: main
title: Deployment Tool for PHP
---
<h2><a name="introduction">Introduction</a></h2>

<p class="lead">
    Deployer is a deployment tool written in PHP, it's simple and functional.
    Deploy your code to all servers you want, it's support deploy via copy, or via VCS (like git), or via rsync.
    Run your tasks on all your servers, or use our recipes of common tasks for Symfony, Laravel, Zend Framework and Yii.
</p>



Create simple deployment script `deploy.php`:

~~~ php
require 'recipe/symfony.php';

server('main', 'domain.com')
    ->user('you');

set('repository', 'git@github.com:you/project.git');

alter('deploy', function () {
    run('service php5-fpm reload');
});
~~~

And deploy your project with command:

~~~
dep deploy
~~~

If something went wrong:

~~~
dep rollback
~~~









<h2><a name="tasks">Tasks</a></h2>

You can define your own tasks in `deploy.php` file.
Then you run `dep` command, Deployer will scan current dir for `deploy.php` file and include it.

To define you own tasks use `task` function:

~~~ php
task('my_task', function () {
    // Your tasks code...
});
~~~

To run your tasks run next command:

~~~
dep my_task
~~~

To list all available commands run command:

~~~
dep list
~~~

You can give you task a description with `desc` method:

~~~ php
task('my_task', function () {
    // Your tasks code...
})->desc('Doing my stuff');
~~~

And then your task will be running you will see this description:

~~~
$ dep my_task
Doing my stuff..............................✔
~~~

To get help of task run help commad:

~~~
dep help deploy
~~~

To run command only on specified server add `--server` option:

~~~
dep deploy --server=main
~~~



<h4><a name="group-tasks">Group tasks</a></h4>

You can combine tasks in groups:

~~~ php
task('deploy', [
    'deploy:start',
    'deploy:prepare',
    'deploy:update_code',
    'deploy:vendors',
    'deploy:symlink',
    'cleanup',
    'deploy:end'
]);
~~~


<h4><a name="before-and-after">Before and after</a></h4>

You can define tasks to be runned before or after some tasks.

~~~ php
task('deploy:done', function () {
    write('Deploy done!');
});

after('deploy', 'deploy:done');
~~~

Then `deploy` task will be called, after this task will be runned `deploy:done` tasks.

Also you can define unnamed task:

~~~ php
before('task', function () {
    // Do your stuff...
});
~~~


<h4><a name="input-options">Using input options</a></h4>

You can define additional input options by calling `option` on your defined tasks.

~~~ php
// Task->option(name, shortcut = null, description = '', default = null);

task('deploy:upload_code', function (InputInterface $input) {
    $branch = $input->getArgument('stage') !== 'production'?$input->getOption('branch',get('branch', null)):get('branch', null);
    ...
})->option('branch', 'b', 'Set the deployed branch', 'develop');


task('deploy', [
    ...
    'deploy:upload_code'
    ...
])->option('branch', 'b', 'Set the deployed branch', 'develop');
~~~
**define the option on the complete chain else it will not be available**

<h2><a name="servers">Servers</a></h2>

Deployer uses ssh2 pecl extension, but if you do not install it on you machine - do not worry,
Deployer will use [PHPSecLib](https://github.com/phpseclib/phpseclib).

You can define servers with `server` function. Here is example of server definition:

~~~ php
server('main', 'site.com')
    ->path('/home/user/site.com')
    ->user('user')
    ->pubKey();
~~~

This function gets 3 parameters `server(server_name, host, port)` and return `Deployer\Server\Configuration` object which contains server configuration.

You need to specify base working path on that server where your project will be deployed with `path()` method.

And specify how to connect to server's SSH. There are a few ways:

<h4><a name="with-username-and-password">With username and password</a></h4>

~~~ php
server(...)
  ->user('name', 'password')
~~~

You can skip password and it will be asked on deploy.

<h4><a name="with-public-key">With public key</a></h4>

~~~ php
server(...)
    ->user('name')
    ->pubKey();
~~~

If you keys created with password or located outside of `.ssh` directory, you can specify it:

~~~ php
server(...)
    ...
    ->pubKey('~/.ssh/id_rsa.pub', '~/.ssh/id_rsa', 'pass phrase');
~~~

Symbol `~` will be replaced with your home directory. If you set pass phrase as `null`,
it will be asked on deploy.

Also you can specify everything with next methods:

~~~ php
server(...)
    ...
    ->setPublicKey(...)
    ->setPrivateKey(...)
    ->setPassPhrase(...);
~~~

<h4><a name="with-config-file">With config file</a></h4>

Deployer can use your SSH config file.

~~~ php
server(...)
    ->user('name')
    ->configFile('/path/to/file');
~~~

This can be used only with installed ssh2 pecl extension.


<h4><a name="with-pem-file">With pem file</a></h4>

Auth with pem file now supported only with PhpSecLib.

~~~ php
// Switch to PhpSecLib
set('use_ssh2', false);

server('ec2', 'host.aws.amazon.com')
    ->user('ec2-user')
    ->pemFile('~/.ssh/keys.pem');
~~~

<h4><a name="upload-and-download">Upload and download</a></h4>

You can upload file or directory with `upload(local, remote)` function.

And download file with `download(local, remote)` function.

<h2><a name="stages">Stages</a></h2>

You can define stages with `stage` function. Here is example of stage definition:

~~~ php
// stage(string name, array serverlist, array options = array(), bool default = true)
stage('development', array('development-server'), array('branch'=>'develop'), true);
stage('production', array('production-primary', 'production-secondary'), array('branch'=>'master'));
~~~

<h4><a name="default-stage">Default stage</a></h4>

You can defined the default stage with `multistage` function. Here is example of stage definition:

~~~ php
multistage('develop');
~~~

<h4><a name="with-options">Options</a></h4>

Besides passing the option through the helper method, it is also possible to add them afterwards.

~~~ php
stage('production', array('production-server'))->options(array('branch'=>'master'));
~~~

It is also possible to set a specific option

~~~ php
stage('production', array('production-server'))->set('branch','master');
~~~

the options will overwrite the ones set in your deploy.php and just like other options you can retrieve them by calling `get`.


<h2><a name="verbosity">Verbosity</a></h2>

Deployer has levels of verbosity. To specify it add one of next options to `dep` command.

* `--quiet (-q)` Do not output any message.
* `--verbose (-v|vv|vvv)` Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug.

In task you can check verbosity level with next methods:

~~~ php
task('my_task', function () {
    if (output()->isQuiet()) {
        // ...
    }

    if (output()->isVerbose()) {
        // ...
    }

    if (output()->isVeryVerbose()) {
        // ...
    }

    if (output()->isDebug()) {
        // ...
    }
});
~~~

<h2><a name="environment">Environment</a></h2>

To get current environment it task call `env()` function.

~~~ php
task('my_task', function () {
    env()->get(...);
});
~~~

To set environment parameter:

~~~ php
env()->set('key', 'value');
~~~

To get environment parameter:

~~~ php
env()->get('key');
~~~

To get release path:

~~~ php
env()->getReleasePath();
~~~

To get server configuration:

~~~ php
config();

// Is same as

env()->getConfig();
~~~

To set <mark>global</mark> Deployer parameters use `set` and `get`:

~~~ php
set('key', 'value');

get('key');
~~~

<h2><a name="functions">Functions</a></h2>

Deployer also provide a lot of helpful functions.

~~~ php
run(string $command)
~~~

Runs command on remote server in working path (`server(...)->path('/woking/path')`).

~~~ php
cd(string $path)
~~~

Sets current working path for `run` functions. Every task restore working path to base working path.

~~~ php
runLocally(string $command)
~~~

Runs command on local machine.


~~~ php
 write(string $message)
~~~

Write message in console. You can format message with tags `<info>...</info>`, `<comment></comment>`, `<error></error>`.


~~~ php
 writeln(string $message)
~~~

Same as `write` function, but also writes new line.


~~~ php
ask(string $message, mixed $default)
~~~

Ask user for input. You need to specify default value. This default value will be used in quiet mode too.


~~~ php
askConfirmation(string $message[, bool $default = false])
~~~

Ask user for yes or no input.


~~~ php
askHiddenResponse(string $message)
~~~

Ask user for password.

~~~ php
output()
~~~

Current console output.

<h2><a name="recipes">Recipes</a></h2>

Deployer has a set of predefined tasks called <mark>recipes</mark>.

Recipes can be included to your `deploy.php` file like this:

~~~ php
require 'recipe/common.php'
~~~

Deployer add recipe directory to include path.

<h4><a name="common-recipe">Common Recipe</a></h4>

This is common recipe use for all other recipes. This recipe creates next directory structure:

~~~
|-- current → /var/www/site.com/releases/20140812131123
|-- releases
|   `-- 20140812131123
|   `-- 20140809150234
|   `-- 20140801145678
`-- shared
   |-- web
   |   `-- uploads
   |-- log
   `-- config
       `-- databases.yml
~~~

~~~
deploy:prepare
~~~

This task prepare server for deploy, create `releases` and `shared` directories.

* `releases` - here will be your project releases.
* `shared` - shared/common files and directories between releases.

~~~
deploy:update_code
~~~

Uploads code from repository and puts it to `releases` directory.

Use `set` function to specify which repository to use:

~~~ php
set('repository', 'git@github.com:user/project.git');
~~~

Remote server has to be able to clone your repository.

~~~
deploy:shared
~~~

Creates symlink to shared files and directories. Use `set` to define them.

~~~ php
set('shared_dirs', ['app/logs']);

set('shared_files', ['app/config/parameters.yml']);
~~~


~~~
deploy:writeable_dirs
~~~

Creates writeable dirs.

~~~ php
set('writeable_dirs', ['app/cache', 'app/logs']);
~~~

~~~
deploy:vendors
~~~

Installs vendors with composer.

~~~
deploy:symlink
~~~

Create symlink `current` to last release.

~~~
cleanup
~~~

Remove old releases and save 3 last. To change this:

~~~ php
get('keep_releases', 3);
~~~

~~~
rollback
~~~

Rollback to previous release.

<h4><a name="composer-recipe">Composer Recipe</a></h4>

~~~ php
require 'recipe/composer.php'
~~~

Simple recipe suitable for simple project which uses composer.

Consists of next tasks:

* deploy:start
* deploy:prepare
* deploy:update_code
* deploy:vendors
* deploy:symlink
* cleanup
* deploy:end

<h4><a name="symfony-recipe">Symfony Recipe</a></h4>

~~~ php
require 'recipe/symfony.php'
~~~

Recipe for deploying Symfony2 projects.
 
Consists of next tasks:

* deploy:start
* deploy:prepare
* deploy:update_code
* deploy:shared
* deploy:writeable_dirs
* deploy:assets
* deploy:vendors
* deploy:assetic:dump
* database:migrate
* deploy:cache:warmup
* deploy:symlink
* cleanup
* deploy:end

Default parameters of this recipre:

~~~ php
// Symfony Environment
set('env', 'prod');

// Symfony shared dirs
set('shared_dirs', ['app/logs']);

// Symfony shared files
set('shared_files', ['app/config/parameters.yml']);

// Symfony writeable dirs
set('writeable_dirs', ['app/cache', 'app/logs']);

// Assets
set('assets', ['web/css', 'web/images', 'web/js']);

// In "-v" verbose mode will be asked to migrate
set('auto_migrate', false);
~~~


<h2><a name="examples">Examples</a></h2>

This example of `deploy.php` script reload php5-fpm service after deploying.

~~~ php
require 'recipe/symfony.php';

server('main', 'site.com')
    ->path('/home/user/site.com')
    ->user('user')
    ->pubKey();

set('repository', 'git@github.com:user/site.git');

task('php-fpm:reload', function () {
	run("sudo /usr/sbin/service php5-fpm reload");
})->description('Reloading PHP5-FPM');

after('deploy:end', 'php-fpm:reload');
~~~