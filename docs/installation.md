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
eval "$(dep autocomplete --install)"
```
:::

## Per project

To install Deployer in your project, use Composer:

```sh
composer require --dev deployer/deployer
```
