---
layout: main
title: Deployment Tool for PHP
---
<h2><a name="introduction">Introduction</a></h2>

There are a lot of deployment tools, even in php. But none of them are simple and functional like Deployer.

Here is simple example of deployment script (deploy.php):

~~~ php
require 'recipe/symfony.php';

server('main', 'domain.com')
    ->user('you')
    ->pubKey();

server('test', 'test.domain.com')
    ->user('you');

task('deploy:done', function () {
    write('Deploy done!');
});

alter('deploy', 'deploy:done');
~~~

<h2><a name="get-started">Get started</a></h2>

TODO

<h2><a name="installation">Installation</a></h2>

TODO