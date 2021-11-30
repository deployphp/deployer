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
eval "$(dep autocomplete --shell bash)"
```
:::

## Distribution

```sh
composer require deployer/dist --dev
```

:::tip Global and local Deployer
If you call a globally installed Deployer via `/usr/local/bin/dep` in a project 
directory with a locally installed Deployer at `vendor/bin/dep`, Deployer will
redirect the call to a local Deployer.

```
~/project $ dep --version
Using ~/project/vendor/bin/dep
Deployer 7.0.0
```
:::

## Source

```sh
composer require deployer/deployer --dev
```

:::warning Dependency conflicts
In case of dependency conflicts, install [distribution](#distribution) version.
:::
