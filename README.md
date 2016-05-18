Introduction
------------
[![Build Status](https://travis-ci.org/deployphp/deployer.svg?branch=master)](https://travis-ci.org/deployphp/deployer)
[![Code Quality](http://img.shields.io/scrutinizer/g/deployphp/deployer.svg?style=flat)](https://scrutinizer-ci.com/g/deployphp/deployer/)
[![Code Climate](http://img.shields.io/codeclimate/github/deployphp/deployer.svg?style=flat)](https://codeclimate.com/github/deployphp/deployer)
[![Code Coverage](http://img.shields.io/scrutinizer/coverage/g/deployphp/deployer.svg?style=flat)](https://scrutinizer-ci.com/g/deployphp/deployer/)
[![Version](http://img.shields.io/packagist/v/deployer/deployer.svg?style=flat)](https://packagist.org/packages/deployer/deployer)

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/69072898-df4a-4dee-ab05-c2ae83d6c469/big.png)](https://insight.sensiolabs.com/projects/69072898-df4a-4dee-ab05-c2ae83d6c469)

Deployer is a deployment tool written in PHP.

See [http://deployer.org](http://deployer.org) for more information and documentation.

[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/deployphp/deployer?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge)

Requirements
------------
* PHP 5.5.0 and up.

That's all!

You can enable [sockets](http://php.net/manual/en/book.sockets.php) to speedup deployment process with parallel deployment.

Installation
------------
To install Deployer download [deployer.phar](http://deployer.org/deployer.phar) archive and move deployer.phar to your bin directory and make it executable.

``` sh
$ curl -L http://deployer.org/deployer.phar -o deployer.phar
$ mv deployer.phar /usr/local/bin/dep
$ chmod +x /usr/local/bin/dep
```

To upgrade Deployer run command:

``` sh
$ dep self-update
```

Or via composer:

``` sh
$ composer require deployer/deployer
```


Documentation
-------------
Documentation source can be found in [deployphp/docs](https://github.com/deployphp/docs) repository.


Building
--------
To build `deployer.phar` run `./build` command.


Community
-------
Discuss Deployer here [deployer.org/discuss](http://deployer.org/discuss).

Roadmap
-------
#### 4 
* Namespace for functions
* Dependency Injection Configuration
* Event Dispatcher
* Default stage selector
* ~~Faster cloning by borrowing objects from existing clones~~ Done in v3!
* Immutable settings
* Native SSH support

#### 4.1
* Task grouping for parallel execution
* Web-based client

Contributing
------------
All code contributions must go through a pull request and approved by a core developer before being merged.
This is to ensure proper review of all the code.

Fork the project, create a feature branch, and send a pull request.

To ensure a consistent code base, you should make sure the code follows
the [PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md).

If you would like to help take a look at the [list of issues](https://github.com/deployphp/deployer/issues).


Maintainers
-----------

* Anton Medvedev [@elfet](https://github.com/elfet)
* Oanh Nguyen [@oanhnn](https://github.com/oanhnn)
* Hubert Viktor [@ZeeCoder](https://github.com/ZeeCoder)

See also the list of [contributors](https://github.com/deployphp/deployer/graphs/contributors) who participated in this project.


License
-------
Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
