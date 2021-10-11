# Basics

Deployer has two main concepts: [**hosts**](hosts.md) and [**tasks**](tasks.md).

A **recipe** is a file containing definitions for **hosts** and **tasks**.

Deployer CLI requires two arguments to run: a **task** to run and a **host**
or group of **hosts**.

```
$ dep deploy deployer.org
  --- ------ ------------
   |    |         |
   |    |         `--- The host
   |    `------------- The task
   `------------------ The CLI
```

Then Deployer takes the given task, performs some preparation (described later),
and executes the task on all specified hosts.

:::note
The `dep` CLI looks for `deploy.php` or `deploy.yaml` file in current directory.
Or recipe can be specified explicitly via `-f` or `--file` option.
```bash
$ dep --file=deploy.php deploy deployer.org
```
:::

Let's write a recipe. 

```php
<?php

// We are going to use functions declared primarily in Deployer namespace,
// to simplify recipe we will use Deployer namespace too. Alternativly, 
// you can import individual functions via "use function".
namespace Deployer;

host('deployer.org');

task('deploy', function () {
    run('whoami');
}); 
```

Let's make sure what we can connect to our host.

```
dep ssh 
```

:::note
If no host provided, Deployer will show an interactive prompt for selecting hosts.
If your recipe contains only one host, Deployer will automatically choose it. 
:::

