Introduction
------------
[![Build Status](http://img.shields.io/travis/elfet/deployer/develop.svg?style=flat)](https://travis-ci.org/elfet/deployer)
[![HHVM Tested](http://img.shields.io/hhvm/elfet/deployer/dev-develop.svg?style=flat)](http://hhvm.h4cc.de/package/elfet/deployer)
[![Code Quality](http://img.shields.io/scrutinizer/g/elfet/deployer/develop.svg?style=flat)](https://scrutinizer-ci.com/g/elfet/deployer/?branch=develop)
[![Code Climate](http://img.shields.io/codeclimate/github/elfet/deployer.svg?style=flat)](https://codeclimate.com/github/elfet/deployer)
[![Code Coverage](http://img.shields.io/scrutinizer/coverage/g/elfet/deployer/develop.svg?style=flat)](https://scrutinizer-ci.com/g/elfet/deployer/?branch=develop)
[![Version](http://img.shields.io/packagist/v/elfet/deployer.svg?style=flat)](https://packagist.org/packages/elfet/deployer)
[![Support via Gittip](http://img.shields.io/gittip/elfet.svg?style=flat)](https://www.gittip.com/elfet)

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/69072898-df4a-4dee-ab05-c2ae83d6c469/big.png)](https://insight.sensiolabs.com/projects/69072898-df4a-4dee-ab05-c2ae83d6c469)


Deployer is a deployment tool written in PHP.

See [http://deployer.in](http://deployer.in) for more information and documentation.


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
composer require elfet/deployer:*
~~~


Documentation
-------------
Documentation can be found on site [deployer.in](http://deployer.in).


Building
--------
To build deployer.phar run `./build` command.

To create new release, clone this repository at `pages` dir, checkout `gh-pages` branch and run `./build -v=VERSION`.
This command will create phar archive in `pages/releases` dir and automatically updates `manifest.json` file.


Contributing
------------
All code contributions must go through a pull request and approved by a core developer before being merged.
This is to ensure proper review of all the code.

Fork the project, create a feature branch, and send a pull request.

To ensure a consistent code base, you should make sure the code follows
the [Coding Standards](http://symfony.com/doc/master/contributing/code/standards.html)
which borrowed from Symfony.

If you would like to help take a look at the [list of issues](https://github.com/elfet/deployer/issues).

To make a release update version number in `bin/dep` file. And run `./build -v=VERSION` command.

License
-------
Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
