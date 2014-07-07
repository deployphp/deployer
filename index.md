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

<h2><a name="getting-started">Getting Started</a></h2>

To install Deployer download <mark>deployer.phar</mark> archive.

<a class="btn btn-primary btn-lg" href="deployer.phar">Download PHAR</a>

Then move deployer.phar to your bin directory and make it executable.

~~~
mv deployer.phar /usr/local/bin/dep
chmod +x /usr/local/bin/dep
~~~

Now you can use Deployer via `dep` command. Later, to update Deployer to latest version run `dep update` command.

Next create `deploy.php` file in your project directory. Let's imagine that you project is based on Symfony2 Framework
(other frameworks described in docs), so it will be good to use existing recipe for deploying Symfony.

~~~ php
require 'recipe/symfony.php';

// Define server for deploy.
// Let's name it "main" and use 22 port.
server('main', 'domain.net', 22)
    ->path('/home/your/project') // Define base path to deploy you project.
    ->user('name', 'password');  // Define SSH user name and password.
                                 // You can skip password and it will be asked on deploy.
                                 // Also you can connect to server SSH via public keys and ssh config file.

// Specify repository from which to download your projects code.
// Server has to be able clone your project from this repository.
set('repository', 'git@bitbucket.org:elfchat/elfchat.net.git');
~~~

Now open in terminal your project directory and run next command:

~~~
dep deploy
~~~

To list all available commands, run `dep` command.

<h2><a name="installation">Installation</a></h2>

TODO
