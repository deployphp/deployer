# Installation

Phar is the best distribution mechanism for a tool.

### Globally

To install Deployer as phar archive globally, run the following commands:

```sh
curl -LO https://deployer.org/deployer.phar
mv deployer.phar /usr/local/bin/dep
chmod +x /usr/local/bin/dep
```

> After installing Deployer globally with will be able to initialize a 
> recipe in new project with just `dep init` command.

If you need another version of Deployer, you can find it on 
the [download page](https://deployer.org/download).
Later, to upgrade Deployer, run the command:

```sh
dep self-update
```

To upgrade to the next major release, if available, use the `--upgrade (-u)` 
option:

```sh
dep self-update --upgrade
```

### Distribution

```sh
composer require deployer/dist --dev
```

Then to use Deployer, run the following command:

```sh
php vendor/bin/dep
```

This is preferred method of installing Deployer in composer project. In 
distribution repo only phar is committed, and its dependencies will not 
conflict with your project dependencies.


Download `deployer.phar` and commit it. Totally okay. Update it via 
with `dep self-update`.

### Source

Use it if you really need Deployer source files.  

```sh
composer require deployer/deployer --dev
```

### Autocomplete

Deployer comes with an autocomplete support for bash & zsh, so you don't need to 
remember task names and options. 

Add next line to you `.bash_profile`:

```shell
eval "$(dep autocomplete)"
```

Read [getting started](getting-started.md) next.
