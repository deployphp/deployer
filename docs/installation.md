# Installation

To install Deployer, run next command in your project dir:
```
composer require --dev deployer/deployer
```

To initialize deployer in you project run:

```
vendor/bin/deployer.phar init
```

:::tip Bash integration
Add next alias to your _.bashrc_ file:

```bash
alias dep='vendor/bin/deployer.phar'
```

Deployer comes with autocomplete support for task names, options, and hosts.

Run the next command to add bash completion support:
```
dep completion bash > /etc/bash_completion.d/deployer
```

Make sure what your _.bashrc_ file includes generated file in some way.
:::
