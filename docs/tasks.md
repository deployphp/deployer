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

### desc()     

Sets task's description.   

```php
task('deploy', function () {
    // ...
})->desc('Task description');
```

Same as using [desc()](api.md#desc) func helper:

```php
desc('Task description');
task('deploy', function () {
    // ...
});
```

### once()       

Sets the task to run only on one of selected hosts.     

### oncePerNode()

Sets the task to run only on **one node** of selected hosts.

Node determined by [hostname](hosts.md#hostname). For example a few different 
hosts can be deploying to one physical machine (uniq hostname). 

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

Hides task from CLI usage page.                         

### addBefore()       

Adds before hook to the task.                           

### addAfter()        

Adds after hook to the task.                            

### limit()             

Limits number of hosts the task will be executed in parallel.

Default is unlimited (runs the task on all host in parallel). 

### select()      

Sets task's host selector.

### addSelector() 

Adds task's selector.                                   

### verbose() 

Makes task always verbose. As if `-v` option persists.  

### disable()                     

Disables the task. Task will not be executed.                                      

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
