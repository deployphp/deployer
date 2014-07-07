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

<a class="btn btn-primary btn-lg" href="deployer.phar">Download PHAR</a>

<h2><a name="installation">Installation</a></h2>

TODO