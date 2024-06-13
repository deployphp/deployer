# Tasks

Define a task by using the [task](api.md#task) function. Also, you can give a description
for a task with the [desc](api.md#desc) function called before _task_:

```php
desc('My task');
task('my_task', function () {
    ....
});
```

To get the task or override task config, call the _task_ function without the second argument:

```php
task('my_task')->disable();
```

## Task config

### desc()

Sets task's description.

```php
task('deploy', function () {
    // ...
})->desc('Task description');
```

Same as using [desc()](api.md#desc) function helper:

```php
desc('Task description');
task('deploy', function () {
    // ...
});
```

### once()

Sets the task to run only on one of the selected hosts.

### oncePerNode()

Sets the task to run only on **one node** of the selected hosts.

The node is identified by its [hostname](hosts.md#hostname). For instance,
multiple hosts might deploy to a single physical machine (with a unique hostname).


```php
host('foo')->setHostname('example.com');
host('bar')->setHostname('example.com');
host('pro')->setHostname('another.com');

task('apt:update', function () {
    // This task will be executed twice, only on "foo" and "pro" hosts.
    run('apt-get update');
})->oncePerNode();
```

### hidden()

Hides the task from CLI usage page.

### addBefore()

Adds a before hook to the task.

### addAfter()

Adds an after hook to the task.

### limit()

Limits the number of hosts the task will be executed on in parallel.

Default is unlimited (runs the task on all hosts in parallel).

### select()

Sets the task's host selector.

### addSelector()

Adds the task's selector.

### verbose()

Makes the task always verbose, as if the `-v` option is persistently enabled.

### disable()

Disables the task. the task will not be executed.

### enable()

Enables the task.

## Task grouping

You can combine tasks in groups:

```php
task('deploy', [
    'deploy:prepare',
    'deploy:update_code',
    'deploy:vendors',
    'deploy:symlink',
    'cleanup'
]);
```

## Task hooks

You can define tasks to be run before or after specific tasks.

```php
task('deploy:done', function () {
    writeln('Deploy done!');
});

after('deploy', 'deploy:done');
```

After the `deploy` task executed, `deploy:done` will be triggered.

:::note
You can see which hooks are enabled via the **dep tree** command.

```
dep tree deploy
```

:::
