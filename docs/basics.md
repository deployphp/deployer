# Basics

Deployer has two main concepts: [**hosts**](hosts.md) and [**tasks**](tasks.md).

A **recipe** is a file containing definitions for **hosts** and **tasks**.

Deployer CLI requires two arguments to run: a **task** to run and a **selector**.

Hosts can also be [selected via labels](hosts.md#labels), also a default host selection can be configured.

```
$ dep deploy deployer.org
  --- ------ ------------
   |    |         |
   |    |         `--- Selector
   |    `------------- Task
   `------------------ CLI
```

Deployer uses the [selector](selector.md) to choose hosts. Next, it takes the given
task, performs some preparation (described later), and executes the task on all
selected hosts.

If a selector is not specified, Deployer will ask you to choose a host from a list.
If your recipe contains only one host, Deployer will automatically choose it.
To select all hosts, specify a special selector: `all`.

The `dep` CLI looks for a `deploy.php` or `deploy.yaml` file in the current directory.

Or a recipe can be specified explicitly via `-f` or `--file` option.

```
$ dep --file=deploy.php deploy deployer.org
```

Let's write a recipe.

```php
// We are going to use functions declared primarily in the Deployer namespace,
// to simplify the recipe, we will also use the Deployer namespace. Alternatively,
// you can import individual functions via "use function".
namespace Deployer;

host('deployer.org');

task('my_task', function () {
    run('whoami');
});
```

Let's try to run our task on deployer.org.

```
$ dep my_task
task my_task
$
```

If no host is provided and no default_selector is set, Deployer will show an interactive prompt for selecting hosts.
If your recipe contains only one host, Deployer will automatically choose it. 
To select all hosts specify `all`.

But where is our `whoami` command output? By default, Deployer runs with normal verbosity
level and shows only the names of executed tasks. Let's increase verbosity to verbose, and
rerun our task.

Add `-v` option to increase verbosity. Read more about [CLI usage](cli.md).

```
$ dep my_task -v
task my_task
[deployer.org] run whoami
[deployer.org] deployer
$
```

Now let's add a second host:

```php
host('deployer.org');
host('medv.io');
```

How does Deployer know how to connect to a host? It uses the same `~/.ssh/config` file as
the `ssh` command. Alternatively, you can specify [connection options](hosts.md) in the recipe.

Let's run `my_task` task on both hosts:

```
$ dep my_task -v all
task my_task
[deployer.org] run whoami
[medv.io] run whoami
[medv.io] anton
[deployer.org] deployer
```

Deployer runs a task in parallel on each host. This is why the output is mixed.
We can limit it to run only on one host at a time.

```
$ dep my_task -v all --limit 1
task my_task
[deployer.org] run whoami
[deployer.org] deployer
[medv.io] run whoami
[medv.io] deployer
```

It is also possible to specify a [limit level](tasks.md#limit) for each individual task.
By specifying the limit level for each task, you can control the degree of parallelism 
for each part of your deployment process.

Each host has a configuration: a list of key-value pairs. Let's define our first
configuration option for both our hosts:

```php
host('deployer.org')
    ->set('my_config', 'foo');
host('medv.io')
    ->set('my_config', 'bar');
```

In the task we can get the currently executing host using the [currentHost](api.md#currenthost) function:

```php
task('my_task', function () {
    $myConfig = currentHost()->get('my_config');
    writeln("my_config: " . $myConfig);
});
```

Or with the [get](api.md#get) function:

```diff
task('my_task', function () {
-   $myConfig = currentHost()->get('my_config');
+   $myConfig = get('my_config');
    writeln("my_config: " . $myConfig);
});
```

Or via the [parse](api.md#parse) function which replaces the `{{ ... }}` brackets 
and their enclosed values with the corresponding configuration option.

All functions (writeln, run, runLocally, cd, upload, etc) call the **parse** function
internally. So you don't need to call the **parse** function by yourself.

```diff
task('my_task', function () {
-   $myConfig = get('my_config');
-   writeln("my_config: " . $myConfig);
+   writeln("my_config: {{my_config}}");
});
```

Let's try to run our task:

```
$ dep my_task all
task my_task
[deployer.org] my_config: foo
[medv.io] my_config: bar
```

Awesome! Each host configuration inherits global configuration. Let's refactor
our recipe to define one global config option:

```php
set('my_config', 'global');

host('deployer.org');
host('medv.io');
```

The config option `my_config` will be equal to `global` on both hosts.

Additionally, the value of a config option can be defined as a callback. 
This callback is executed upon its first access, and the returned result 
is then stored in the host configuration.

```php
set('whoami', function () {
    return run('whoami');
});

task('my_task', function () {
    writeln('Who am I? {{whoami}}');
});
```

Let's try to run it:

```
$ dep my_task all
task my_task
[deployer.org] Who am I? deployer
[medv.io] Who am I? anton
```

We can use this to create a dynamic configuration which uses information from the current host.

Only the first call will trigger the callback execution. All subsequent checks use the previously 
saved value.


Here is an example:

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

If we run my_task, we will see that `date` is called only once on
`{{current_date}}` access.

```
$ dep my_task deployer.org -v
task my_task
[deployer.org] run date
[deployer.org] Wed 03 Nov 2021 01:16:53 PM UTC
[deployer.org] What time is it? Wed 03 Nov 2021 01:16:53 PM UTC
[deployer.org] run sleep 5
[deployer.org] What time is it? Wed 03 Nov 2021 01:16:53 PM UTC
```

We can override a config option via CLI option `-o` like this:

```
$ dep my_task deployer.org -v -o current_date="I don't know"
task my_task
[deployer.org] What time is it? I don't know
[deployer.org] run sleep 5
[deployer.org] What time is it? I don't know
```

Since the `current_date` config option is overridden there is no need to call the callback.
So there is no 'run date'.
