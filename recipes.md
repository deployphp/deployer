---
layout: main
title: Recipes
---

# Recipes

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

### Composer Recipe

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


### Symfony Recipe

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

&larr; [Functions](functions.html) &divide; [Examples](examples.html) &rarr;