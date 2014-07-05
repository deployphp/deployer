---
layout: main
title: Deployment Tool for PHP
---
<h2>
    <a name="introduction" class="anchor" href="#introduction"><span class="octicon octicon-link"></span></a>Introduction
</h2>

<p>There are a lot of deployment tools, even in php. But none of them are simple and functional like Deployer.</p>

<p>Here is simple example of deployment script (deploy.php):</p>

```
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
```