Introduction
------------
[![Build Status](http://img.shields.io/travis/deployphp/deployer/2.x.svg?style=flat)](https://travis-ci.org/deployphp/deployer)
[![Support via Gittip](http://img.shields.io/gittip/elfet.svg?style=flat)](https://www.gittip.com/elfet)

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/69072898-df4a-4dee-ab05-c2ae83d6c469/big.png)](https://insight.sensiolabs.com/projects/69072898-df4a-4dee-ab05-c2ae83d6c469)


Deployer is a deployment tool written in PHP.

See [deployer.org](http://deployer.org) for more information and documentation.


Requirements
------------
Deployer is only supported on PHP 5.4.0 and up (yes, it's time to grow up).
Deployer work with ssh2 pecl extension, but if you do not install it on you machine - do not worry,
Deployer will work with [PHPSecLib](https://github.com/phpseclib/phpseclib).


Installation
------------
To install Deployer download [deployer.phar](http://deployer.org/deployer.phar) archive and move deployer.phar to your bin directory and make it executable.

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
composer require elfet/deployer:*
~~~


Documentation
-------------
Documentation can be found on site [deployer.org](http://deployer.org).


Building
--------
To build deployer.phar run `./build` command.

Recipes
-------
Deployer contains core and popular recipes. For 3rd party and user contributed recipes there is a repository [deployphp/recipes](http://github.com/deployphp/recipes) which contains all of these which extend Deployer's core recipes. You can see all available recipes at http://github.com/deployphp/recipes

You can install it by calling `composer require --dev "deployphp/recipes >=1.0@dev"`.
To use a recipe you can simply require it in your `deploy.php` file by inserting:
`require 'vendor/deployphp/recipes/recipes/recipe_name.php';`

Contributing
------------
All code contributions must go through a pull request and approved by a core developer before being merged.
This is to ensure proper review of all the code.

Fork the project, create a feature branch, and send a pull request.

To ensure a consistent code base, you should make sure the code follows
the [Coding Standards](http://symfony.com/doc/master/contributing/code/standards.html)
which borrowed from Symfony.

If you would like to help take a look at the [list of issues](https://github.com/deployphp/deployer/issues).

To make a release update version number in `bin/dep` file. And run `./build -v=VERSION` command.

License
-------
Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
