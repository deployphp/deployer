# Basics

Deployer has two main concepts: [**hosts**](hosts.md) and [**tasks**](tasks.md).

A **recipe** is a file containing definitions for **hosts** and **tasks**.

Deployer CLI requires two arguments to run: a **task** to run and a **selector**.

```
$ dep deploy deployer.org
  --- ------ ------------
   |    |         |
   |    |         `--- Selector
   |    `------------- Task
   `------------------ CLI
```

Deployer uses the [selector](selector.md) to choose hosts. Next takes the given
task, performs some preparation (described later), and executes the task on all
selected hosts.

If selector not specified Deployer will ask you to choose host from list.
If your recipe contains only one host, Deployer will automatically choose it.
To select all hosts specify a special selector: `all`.

The `dep` CLI looks for `deploy.php` or `deploy.yaml` file in current directory.

Or recipe can be specified explicitly via `-f` or `--file` option.

```
$ dep --file=deploy.php deploy deployer.org
```

Let's write a recipe.

```php
// We are going to use functions declared primarily in Deployer namespace,
// to simplify recipe we will use Deployer namespace too. Alternativly,
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

But where is our `whoami` command output? By default, Deployer runs with normal verbosity
level and shows only names of executed tasks. Let's increase verbosity to verbose, and
rerun our task.

Add `-v` option to increase verbosity. Read more about [CLI usage](cli.md).

```
$ dep my_task -v
task my_task
[deployer.org] run whoami
[deployer.org] deployer
$
```

Now let's add second host:

```php
host('deployer.org');
host('medv.io');
```

How does Deployer know how to connect to a host? It uses same `~/.ssh/config` file as
the `ssh` command. Alternatively, you can specify [connection options](hosts.md) in recipe.

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

Limit level also possible to [specified per task](tasks.md#limit).

Each host has a configuration: a list of key-value pairs. Let's define our first
configuration option for both our hosts:

```php
host('deployer.org')
    ->set('my_config', 'foo');
host('medv.io')
    ->set('my_config', 'bar');
```

In the task we can get current executing host with [currentHost](api.md#currenthost) function:

```php
task('my_task', function () {
    $myConfig = currentHost()->get('my_config');
    writeln("my_config: " . $myConfig);
});
```

Or with [get](api.md#get) function:

```diff
task('my_task', function () {
-   $myConfig = currentHost()->get('my_config');
+   $myConfig = get('my_config');
    writeln("my_config: " . $myConfig);
});
```

Or via [parse](api.md#parse) function which replaces brackets `{{ ... }}` and value
with of config option.

All functions (writeln, run, runLocally, cd, upload, etc) call **parse** function
internally. So you don't need to call **parse** function by your self.

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

Also, config option value can be specified as a callback, such callback
executed on first access and returned result saved in host configuration.

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

We can use this to create dynamic configuration which uses current host information.

Only the first call will trigger the callback execution. All subsequent checks use saved value.

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

If we run my_task we will see that `date` is called only once on
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
