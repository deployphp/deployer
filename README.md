Introduction
------------
[![Build Status](http://img.shields.io/travis/elfet/deployer.svg?style=flat)](https://travis-ci.org/elfet/deployer)
[![HHVM Tested](http://img.shields.io/hhvm/elfet/deployer.svg?style=flat)](http://hhvm.h4cc.de/package/elfet/deployer)
[![Code Quality](http://img.shields.io/scrutinizer/g/elfet/deployer.svg?style=flat)](https://scrutinizer-ci.com/g/elfet/deployer/)
[![Code Climate](http://img.shields.io/codeclimate/github/elfet/deployer.svg?style=flat)](https://codeclimate.com/github/elfet/deployer)
[![Code Coverage](http://img.shields.io/scrutinizer/coverage/g/elfet/deployer.svg?style=flat)](https://scrutinizer-ci.com/g/elfet/deployer/)
[![Version](http://img.shields.io/packagist/v/elfet/deployer.svg?style=flat)](https://packagist.org/packages/elfet/deployer)
[![Support via Gittip](http://img.shields.io/gittip/elfet.svg?style=flat)](https://www.gittip.com/elfet)

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/69072898-df4a-4dee-ab05-c2ae83d6c469/big.png)](https://insight.sensiolabs.com/projects/69072898-df4a-4dee-ab05-c2ae83d6c469)


Deployer is a deployment tool written in PHP.

See [http://deployer.in](http://deployer.in) for more information and documentation.

Deployer 3.0
============
Deployer 3.0 developing roadmap:

* [x] Refactor Source
  * [x] Refactor tasks
  * [x] Refactor servers
  * [x] Refactor Deployer class
* [ ] New features
  * [ ] Environment inheritance
  * [ ] Local tasks `->once()`
  * [ ] Parallel task execution
  * [ ] Better output 
  * [ ] Reach 100% code coverage
* [ ] Refactor recipes
  * [ ] Refactor common recipe
  * [ ] Refactor Symfony recipe 
* [ ] New recipes
  * [ ] Zend Framework recipe
  * [ ] Laravel recipe
  * [ ] WordPress recipe
  * [ ] Yii recipe
* [ ] New site on deployer.org
  * [ ] Separate docs and site code
  * [ ] Auto updating docs script
  * [ ] Auto phar build script


Requirements
------------
* PHP 5.4.0 and up.

That's all!

You can install [ssh2 extension](http://php.net/manual/en/book.ssh2.php) to speedup deployment process and enable [sockets](http://php.net/manual/en/book.sockets.php) for parallel deployment.


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
composer require elfet/deployer:*
~~~


Documentation
-------------
Documentation can be found on site [deployer.in](http://deployer.in).


Building
--------
To build deployer.phar run `bin/build` command.


Contributing
------------
All code contributions must go through a pull request and approved by a core developer before being merged.
This is to ensure proper review of all the code.

Fork the project, create a feature branch, and send a pull request.

To ensure a consistent code base, you should make sure the code follows
the [PSR-1](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md).

If you would like to help take a look at the [list of issues](https://github.com/elfet/deployer/issues).

To make a release update version number in `bin/dep` file. And run `./build -v=VERSION` command.

License
-------
Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
