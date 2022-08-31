# Installation

## Globally

To install Deployer as phar archive globally, run the following commands:

```sh
curl -LO https://deployer.org/deployer.phar
mv deployer.phar /usr/local/bin/dep
chmod +x /usr/local/bin/dep
```

:::tip CLI Autocomplete
Deployer comes with autocomplete support for task names, options, and hosts.

Add the following to your `~/.bashrc` or `~/.zshrc`:

```
eval "$(dep autocomplete --install)
```
:::

## Distribution

This is the preferable installation method. The **deployer/dist** contains the 
phar archive checked out at [deployphp/distribution](https://github.com/deployphp/distribution) repo.

```sh
composer require --dev deployer/dist
```

## Source

```sh
composer require --dev deployer/deployer
```

:::warning Dependency conflicts
In case of dependency conflicts, install [distribution](#distribution) version.
:::
