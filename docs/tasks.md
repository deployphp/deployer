# Tasks

Define a tasks by using the [task](api.md#task) function. Also, you can give a description 
for a task with the [desc](api.md#desc) function called before _task_:

```php
desc('My task');
task('my_task', function () {
    ....
});
```

To get the task or override task config call the _task_ function without second argument:

```php
task('my_task')->disable();
```


## Task config

| Method                           | Description                                              |
|----------------------------------|----------------------------------------------------------|
| `desc(string $description)`      | Sets task's description.                                 |
| `once(bool $once = true)`        | Sets the task to run only on one of selected hosts.      |
| `oncePerNode(bool $once = true)` | Sets the task to run only on one node of selected hosts. |
| `hidden(bool $hidden = true)`    | Hides task from CLI usage page.                          |
| `addBefore(string $task)`        | Adds before hook to the task.                            |
| `addAfter(string $task)`         | Adds after hook to the task.                             |
| `limit(int $limit)`              | Limits number of hosts the task will be ran in parallel. |
| `select(string $selector)`       | Sets task's host selector.                               |
| `addSelector(string $selector)`  | Adds task's selector.                                    |
| `verbose(bool $verbose = true)`  | Makes task always verbose. As if `-v` option persists.   |
| `disable()`                      | Disables the task.                                       |
| `enable()`                       | Enables the task.                                        |

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
    write('Deploy done!');
});

after('deploy', 'deploy:done');
```

After the `deploy` task executed, `deploy:done` will be triggered.

:::note
You can see which hooks is enabled via **dep tree** command.
```
dep tree deploy
```
:::
