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