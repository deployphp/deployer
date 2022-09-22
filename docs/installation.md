# Installation

To install Deployer, run next command in your project dir:

```
composer require --dev deployer/deployer
```

To initialize deployer in your project run:

```
vendor/bin/dep init
```

:::tip Bash integration
Add next alias to your _.bashrc_ file:

```bash
alias dep='vendor/bin/dep'
```

Deployer comes with autocomplete support for task names, options, and hosts.

Run the next command to add bash completion support:

```
dep completion bash > /etc/bash_completion.d/deployer
```

Make sure what your _.bashrc_ file includes generated file in some way.
:::
