---
layout: main
title: Deployment Tool for PHP
---

# Introduction

<p class="lead">
    Deployer is a deployment tool written in PHP, it's simple and functional.
    Deploy your code to all servers you want, it supports deploy via copy, or via VCS (like git), or via rsync.
    Run your tasks on all your servers, or use our recipes of common tasks for Symfony, Laravel, Zend Framework and Yii.
</p>

Create simple deployment script `deploy.php`:

~~~ php
require 'recipe/symfony.php';

server('main', 'domain.com')
    ->user('you');

set('repository', 'git@github.com:you/project.git');

after('deploy', function () {
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

[Getting Started](getting-started.html) &rarr;
