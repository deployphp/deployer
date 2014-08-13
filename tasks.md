---
layout: main
title: Tasks
---

# Tasks

You can define your own tasks in `deploy.php` file.
Then you run `dep` command, Deployer will scan current dir for `deploy.php` file and include it.

To define you own tasks use `task` function:

~~~ php
task('my_task', function () {
    // Your tasks code...
});
~~~

To run your tasks run next command:

~~~
dep my_task
~~~

To list all available commands run command:

~~~
dep list
~~~

You can give you task a description with `desc` method:

~~~ php
task('my_task', function () {
    // Your tasks code...
})->desc('Doing my stuff');
~~~

And then your task will be running you will see this description:

~~~
$ dep my_task
Doing my stuff..............................✔
~~~

To get help of task run help commad:

~~~
dep help deploy
~~~

To run command only on specified server add `--server` option:

~~~
dep deploy --server=main
~~~

### Group tasks

You can combine tasks in groups:

~~~ php
task('deploy', [
    'deploy:start',
    'deploy:prepare',
    'deploy:update_code',
    'deploy:vendors',
    'deploy:symlink',
    'cleanup',
    'deploy:end'
]);
~~~


### Before and after

You can define tasks to be runned before or after some tasks.

~~~ php
task('deploy:done', function () {
    write('Deploy done!');
});

after('deploy', 'deploy:done');
~~~

Then `deploy` task will be called, after this task will be runned `deploy:done` tasks.

Also you can define unnamed task:

~~~ php
before('task', function () {
    // Do your stuff...
});
~~~


### Using input options

You can define additional input options by calling `option` on your defined tasks.

~~~ php
// Task->option(name, shortcut = null, description = '', default = null);

task('deploy:upload_code', function (InputInterface $input) {
    $branch = $input->getArgument('stage') !== 'production'?$input->getOption('branch',get('branch', null)):get('branch', null);
    ...
})->option('branch', 'b', 'Set the deployed branch', 'develop');


task('deploy', [
    ...
    'deploy:upload_code'
    ...
])->option('branch', 'b', 'Set the deployed branch', 'develop');
~~~

**define the option on the complete chain else it will not be available**

&larr; [Installation](installation.html) &divide; [Servers](servers.html) &rarr;