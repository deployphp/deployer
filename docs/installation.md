# Installation

## Global installation

To install Deployer, run next command in your project dir:

```
composer global require deployer/deployer
```

```
phive install deployer
```

Run in your project to create `deploy.php` file:

```
dep init
```

:::tip Autocomplete

Deployer comes with autocomplete support for task names, options, and hosts.

Run the next command to add bash completion support:

```
dep completion bash > /etc/bash_completion.d/deployer
```

Make sure what your _.bashrc_ file includes generated file in some way.

:::

## Project installation

To install Deployer in your project, run next command in your project dir:

```
composer require --dev deployer/deployer
```

To initialize deployer in your project run:

```
vendor/bin/dep init
```

## Phar download

You can download deployer phar file from [releases](https://github.com/deployphp/deployer/releases) page.

After downloading, you can run it in your project dir:

```
php deployer.phar init
```
