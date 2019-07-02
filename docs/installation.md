# Installation

There are three ways to install deployer: 

1. download phar archive
2. source composer installation
3. distribution composer installation

### Download phar archive

To install Deployer as phar archive, run the following commands:

```sh
curl -LO https://deployer.org/deployer.phar
mv deployer.phar /usr/local/bin/dep
chmod +x /usr/local/bin/dep
```

If you need another version of Deployer, you can find it on the [download page](https://deployer.org/download).
Later, to upgrade Deployer, run the command:

```sh
dep self-update
```

To upgrade to the next major release, if available, use the `--upgrade (-u)` option:

```sh
dep self-update --upgrade
```

### Source composer installation

To install Deployer source version with Composer, run the command:

```sh
composer require deployer/deployer --dev
```

You can also install it globally:

``` sh
composer global require deployer/deployer
```

More info: https://getcomposer.org/doc/03-cli.md#global

Then to use Deployer, run the following command:

```sh
php vendor/bin/dep
```

> If you have installed Deployer using **both** methods, running `dep` command will prefer a composer-installed version. 

> If you have dependency conflicts you can use "distribution composer installation"

### Distribution composer installation

To install Deployer distribution version with Composer, run the command:

```sh
composer require deployer/dist --dev
```

Then to use Deployer, run the following command:

```sh
php vendor/bin/dep
```

### Own builded phar

If you want to build Deployer from the source code, clone the project from GitHub:

```sh
git clone https://github.com/deployphp/deployer.git
```

Then run the following command in the project directory:

```sh
php bin/build
```

This will build the `deployer.phar` phar archive.


### Autocomplete

Deployer comes with an autocomplete script for bash/zsh/fish, so you don't need to remember all tasks and options.
To install, run the following command:

~~~bash
dep autocomplete
~~~

And follow the instructions. 

Read [getting started](getting-started.md) next.
