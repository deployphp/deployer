Introduction
------------
[![Build Status](https://travis-ci.org/elfet/deployer.png?branch=master)](https://travis-ci.org/elfet/deployer)

There are a lot of deployment tools, even in php. But none of them are simple and functional like Deployer.

Create `deploy.php` in your project:

```php
require 'recipe/symfony.php';

server('main', 'domain.com')
    ->user('you')
    ->pubKey();

server('test', 'test.domain.com')
    ->user('you');

task('deploy:done', function () {
    write('Deploy done!');
});

after('deploy', 'deploy:done');
```

And run command:

```
dep deploy
```

Done! You just deploy your project.

Requirements
------------
Deployer is only supported on PHP 5.4.0 and up (yes, it's time to grow up).
Deployer work with ssh2 pecl extension, but if you do not install it on you machine - do not worry,
Deployer will work with [PHPSecLib](https://github.com/phpseclib/phpseclib).

Installation
------------

To install Deployer download [deployer.phar](http://deployer.in/deployer.phar) archive and move deployer.phar to your bin directory and make it executable.

~~~
mv deployer.phar /usr/local/bin/dep
chmod +x /usr/local/bin/dep
~~~

To upgrade Deployer run command:

~~~
dep self-update
~~~

Or via composer:

~~~
composer require elfet/deployer:~1.0
~~~

Documentation
-------------

Documentation can be found on site [deployer.in](http://deployer.in).

License
-------
Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
