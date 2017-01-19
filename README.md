<p align="center">
<a href="https://deployer.org" target="_blank"><img width="243" src="https://raw.githubusercontent.com/deployphp/deployer.org/master/public/images/deployer.png?v2"></a>
</p>

<p align="center">
<a href="https://travis-ci.org/deployphp/deployer"><img src="https://travis-ci.org/deployphp/deployer.svg?branch=master" alt="Build Status"></a>
<a href="https://scrutinizer-ci.com/g/deployphp/deployer/"><img src="http://img.shields.io/scrutinizer/g/deployphp/deployer.svg?style=flat" alt="Code Quality"></a>
<a href="https://packagist.org/packages/deployer/deployer"><img src="https://img.shields.io/packagist/dt/deployer/deployer.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/deployer/deployer"><img src="http://img.shields.io/packagist/v/deployer/deployer.svg?style=flat" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/deployer/deployer"><img src="https://img.shields.io/badge/license-MIT-blue.svg?style=flat" alt="License"></a>
</p>

## About Deployer

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/69072898-df4a-4dee-ab05-c2ae83d6c469/big.png)](https://insight.sensiolabs.com/projects/69072898-df4a-4dee-ab05-c2ae83d6c469)

Deployer is a deployment tool written in PHP.

See [deployer.org](https://deployer.org) for more information and documentation.

Support Deployer development by [![Becoming a patron](https://img.shields.io/badge/become-patron-brightgreen.svg)](https://www.patreon.com/deployer)

## Requirements
* PHP 5.5.0 and up.

That's all!

You can enable [sockets](http://php.net/manual/en/book.sockets.php) to speedup deployment process with parallel deployment.

## Installation
To install Deployer download [deployer.phar](https://deployer.org/deployer.phar) archive and move deployer.phar to your bin directory and make it executable.

``` sh
curl -LO https://deployer.org/deployer.phar
mv deployer.phar /usr/local/bin/dep
chmod +x /usr/local/bin/dep
```

To upgrade Deployer run command:

``` sh
dep self-update
```

Or via composer:

``` sh
composer require deployer/deployer
```

## Documentation
Documentation source can be found in [deployphp/docs](https://github.com/deployphp/docs) repository.

## Roadmap
* Better documentation.
* Better DX with intelligible errors.
* Better parallel task runner based on https://github.com/icicleio/icicle
* Task grouping for parallel execution.
* Task combining for less ssh calls.
* Implement `dep status` command with health-check of running application and deployment log.
* More deploy strategies.
* More integration with third-party services.
* Web-based client.

## Maintainers
* Anton Medvedev [@elfet](https://github.com/elfet)
* Oanh Nguyen [@oanhnn](https://github.com/oanhnn)

See also the list of [contributors](https://github.com/deployphp/deployer/graphs/contributors) who participated in this project.

## Support
Deployer is an open source project. If you want to support the development of Deployer visit our [patreon page](https://www.patreon.com/deployer).

## License
Licensed under the [MIT license](http://opensource.org/licenses/MIT).
