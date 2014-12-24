---
layout: main
title: Installation
---

# Installation

To install Deployer download <a href="deployer.phar">deployer.phar</a> archive
and move deployer.phar to your bin directory and make it executable.

~~~
mv deployer.phar /usr/local/bin/dep
chmod +x /usr/local/bin/dep
~~~

To upgrade Deployer run command:

~~~
dep self-update
~~~

To redownload update if already using current version add option `--redo (-r)`

~~~
dep self-update --redo
~~~

To allow pre-release updates add option `--pre (-p)`

~~~
dep self-update --pre
~~~

To upgrade to next major release, if available add option `--upgrade (-u)`

~~~
dep self-update --upgrade
~~~

### Via Composer
You can install Deployer with composer:

~~~
composer require elfet/deployer:~2.0
~~~

Then to run Deployer run next command:

~~~
php vendor/bin/dep
~~~

### Source Code

If you want build Deployer from source code, clone project from GitHub:

~~~
git clone git@github.com:elfet/deployer.git
~~~

And run next command in project directory:

~~~
php ./build
~~~

This will build phar archive `deployer.phar`

&larr; [Getting Started](getting-started.html) &divide; [Tasks](tasks.html) &rarr;


