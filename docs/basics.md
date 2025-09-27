# Basics

Deployer operates around two main concepts: [**hosts**](hosts.md) and [**tasks**](tasks.md). These are defined within a
**recipe**, which is simply a file containing **hosts** and **tasks** definitions.

The Deployer CLI requires two arguments:

1. A **task** to execute.
2. A **selector** to determine the hosts the task will run on.

Here's an example:

```sh
$ dep deploy deployer.org
      ------ ------------
       task    selector
```

Deployer uses the [selector](selector.md) to choose which hosts to execute the task on. After selecting hosts, it
prepares the environment (details later) and runs the task.

### Host Selection

- If no selector is specified, Deployer prompts you to choose a host.
- If your recipe has only one host, it is automatically selected.
- To run a task on all hosts, use the `all` selector.

By default, the `dep` CLI looks for a `deploy.php` or `deploy.yaml` file in the current directory. Alternatively, you
can specify a recipe file explicitly using the `-f` or `--file` option:

```sh
$ dep --file=deploy.php deploy deployer.org
```

---

## Writing Your First Recipe

Here's an example of a simple recipe:

```php
namespace Deployer;

host('deployer.org');

task('my_task', function () {
    run('whoami');
});
```

To execute this task on `deployer.org`:

```sh
$ dep my_task
task my_task
```

### Increasing Verbosity

By default, Deployer only shows task names. To see detailed output (e.g., the result of the `whoami` command), use the
`-v` option:

```sh
$ dep my_task -v
task my_task
[deployer.org] run whoami
[deployer.org] deployer
```

---

## Working with Multiple Hosts

You can define multiple hosts in your recipe:

```php
host('deployer.org');
host('medv.io');
```

Deployer connects to hosts using the same `~/.ssh/config` file as the `ssh` command. Alternatively, you can
specify [connection options](hosts.md) directly in the recipe.

Run a task on both hosts:

```sh
$ dep my_task -v all
task my_task
[deployer.org] run whoami
[medv.io] run whoami
[deployer.org] deployer
[medv.io] anton
```

### Controlling Parallelism

By default, tasks run in parallel on all selected hosts, which may mix the output. To limit execution to one host at a
time:

```sh
$ dep my_task -v all --limit 1
task my_task
[deployer.org] run whoami
[deployer.org] deployer
[medv.io] run whoami
[medv.io] deployer
```

You can also specify a [limit level](tasks.md#limit) for individual tasks to control parallelism.

---

## Configuring Hosts

Each host can have a set of key-value configuration options. Here's an example:

```php
host('deployer.org')->set('my_config', 'foo');
host('medv.io')->set('my_config', 'bar');
```

Access these options in a task using the [currentHost](api.md#currenthost) function:

```php
task('my_task', function () {
    $myConfig = currentHost()->get('my_config');
    writeln("my_config: " . $myConfig);
});
```

Or more concisely with the [get](api.md#get) function:

```php
task('my_task', function () {
    $myConfig = get('my_config');
    writeln("my_config: " . $myConfig);
});
```

Or using brackets syntax `{{` and `}}`:

```php
task('my_task', function () {
    writeln("my_config: {{my_config}}");
});
```

---

## Global Configurations

Host configurations inherit global options. Here's how to set a global configuration:

```php
set('my_config', 'global');

host('deployer.org');
host('medv.io');
```

Both hosts will inherit `my_config` with the value `global`. You can override these values for individual hosts as
needed.


```php
set('my_config', 'global');

host('deployer.org');
host('medv.io')->set('my_config', 'bar');
```

---

## Dynamic Configurations

You can define dynamic configuration values using callbacks. These are evaluated the first time they are accessed, and
the result is stored for subsequent use:

```php
set('whoami', function () {
    return run('whoami');
});

task('my_task', function () {
    writeln('Who am I? {{whoami}}');
});
```

When executed:

```sh
$ dep my_task all
task my_task
[deployer.org] Who am I? deployer
[medv.io] Who am I? anton
```

---

Dynamic configurations are cached after the first use:

```php
set('current_date', function () {
    return run('date');
});

task('my_task', function () {
    writeln('What time is it? {{current_date}}');
    run('sleep 5');
    writeln('What time is it? {{current_date}}');
});
```

Running this task:

```sh
$ dep my_task deployer.org -v
task my_task
[deployer.org] run date
[deployer.org] Wed 03 Nov 2021 01:16:53 PM UTC
[deployer.org] What time is it? Wed 03 Nov 2021 01:16:53 PM UTC
[deployer.org] run sleep 5
[deployer.org] What time is it? Wed 03 Nov 2021 01:16:53 PM UTC
```

---

## Overriding Configurations via CLI

You can override configuration values using the `-o` option:

```sh
$ dep my_task deployer.org -v -o current_date="I don't know"
task my_task
[deployer.org] What time is it? I don't know
[deployer.org] run sleep 5
[deployer.org] What time is it? I don't know
```

Since `current_date` is overridden, the callback is never executed.

:::note
If you need to create a new configuration option based on the overridden one, use dynamic configuration syntax:

```php

set('dir_name', 'test');

set('uses_overridden_dir_name', function () {
    return '/path/to/' . get('dir_name');
});

set('uses_original_dir_name', '/path/to/' . get('dir_name'));

task('my_task', function () {
    writeln('Path: {{uses_overridden_dir_name}}');
    writeln('Path: {{uses_original_dir_name}}');
});
```

```sh
$ dep my_task deployer.org -v -o dir_name="prod"
task my_task
[deployer.org] Path: /path/to/prod
[deployer.org] Path: /path/to/test
```
:::

---

By now, you should have a solid understanding of Deployer’s basics, from defining tasks and hosts to working with
configurations and dynamic values. Happy deploying!
