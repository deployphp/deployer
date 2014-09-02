---
layout: main
title: Getting Started
---

# Getting Started

To install Deployer download <mark>deployer.phar</mark> archive.

<a class="btn btn-primary btn-lg" href="deployer.phar">Download PHAR</a>

Then move deployer.phar to your bin directory and make it executable.

~~~
mv deployer.phar /usr/local/bin/dep
chmod +x /usr/local/bin/dep
~~~

Now you can use Deployer via `dep` command. Later, to upgrade Deployer to latest version run `dep self-update` command.

Next create `deploy.php` file in your project directory. Let's imagine that your project is based on Symfony2 Framework
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

&larr; [Introduction](index.html) &divide; [Installation](installation.html) &rarr;
