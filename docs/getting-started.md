# Getting Started

Deployer consists of two main parts: core task runner and recipes.

First, [install the Deployer](installation.md). 

Let's create our first recipe! Create a file named **deploy.php** and define our 
first task and one host we are going to deploy to:

```php
<?php
namespace Deployer;

host('deployer.org');

task('hello', function () {
    run('ls -1');
});
```

> We are using namespace `Deployer` as we're going to primarily use functions
> defined in this namespace. 

Read more about [task's definitions](tasks.md).

Now let's run our task:

```bash
$ dep hello
task hello
```

What just happen? Deployer connected to **deployer.org** and executed `ls -1` 
command. Let's count how many files ls command returned:

```php
task('hello', function () {
    $output = run('ls -1');
    $lines = substr_count($output, "\n");
    writeln("Total files: $lines");
});
```

```bash
$ dep hello
task hello
[deployer.org] Total files: 4
```

Awesome! But let's debug our task and take a look what `ls -1` command actually 
returned. We can place `writeln($output)` call in our task, but there is an 
easier way: just add `-v` option:

```bash
$ dep hello -v
task hello
[deployer.org] run ls -1
[deployer.org] dev
[deployer.org] deployer.org
[deployer.org] medv.io
[deployer.org] numbr.dev
[deployer.org] Total files: 4
```

Adding `-v` option instructs Deployer to print each commands it executes with 
it's output. Looks like our task is executed in wrong directory. Let's cd to
correct one:

```diff
task('hello', function () {
+   cd('~/deployer.org');
    $output = run('ls -1');
    $lines = substr_count($output, "\n");
    writeln("Total files: $lines");
});
```

Tasks and hosts are two main concepts of Deployer. We define what to do in tasks
and hosts define there to run it. Now let's add another host to run out task on:

```php
host('deployer.org');
host('beta.deployer.org');
```

Read more about [host's definition](hosts.md).

Now let's run `dep hello` again. This time Deployer will ask us what hosts do we 
intend to run on. Let's run this command again this time with special selector 
**all** which indicated what we're planning to run our task on all defined 
hosts.

```bash
$ dep hello all
task hello
[deployer.org] Total files: 15
[beta.deployer.org] error in deploy.php on line 8:
[beta.deployer.org] run cd ~/deployer.org && (ls -1)
[beta.deployer.org] err bash: line 1: cd: /home/deployer/deployer.org: 
[beta.deployer.org] err bash: No such file or directory
[beta.deployer.org] exit code 1 (General error)
```

That's right. There is no `~/deployer.org` dir on our beta host. To fix it we 
need to cd in correct dir on each host. To do that, let's define config per 
host.

Each host has own configuration parameters. To access it inside task use 
`get()`/`set()` functions. Also, each Deployer function can parse config 
parameters via `{{...}}` syntax. 

Read more about [host's configuration](config.md).

```php
host('deployer.org')
    ->set('my_path', '~/deployer.org');
host('beta.deployer.org')
    ->set('my_path', '~/beta.deployer.org');
```

And let's use this config in our task:

```diff
task('hello', function () {
-   cd('~/deployer.org');
+   cd(get('my_path'));
    $output = run('ls -1');
    $lines = substr_count($output, "\n");
    writeln("Total files: $lines");
});
```

Let's test it:

```bash
$ dep hello all
task hello
[deployer.org] Total files: 15
[beta.deployer.org] Total files: 15
```

Success! This is a basics of Deployer. We can define hosts with config and 
tasks. Using those we can create our own deployment recipes. 

Deployer comes with a bunch of recipes for most popular frameworks. Let's use,
for example, Laravel recipe to deploy our Laravel project. 

```bash
$ dep init
```

Follow instructions, choose one of types for your recipe: php or yaml. Read more 
about writing [yaml recipes here](yaml.md). Let's choose php recipe for our 
case.

> You can mix php and yaml recipes. For example, you can add yaml recipe to your
> php recipe via `import()` function, or import php recipes from yaml recipe.

Let's take a look on generated **deploy.php** recipe. It requires Laravel 
recipe:

```php
require 'recipe/laravel.php';
```

> In recipes, you can use `require` to import recipes defined in 
> [recipes](https://github.com/deployphp/deployer/tree/master/recipe) and
> [contrib](https://github.com/deployphp/deployer/tree/master/contrib) dirs. But
> you can always require recipes by absolute path.

Then there are three sections: config, hosts and tasks. All other tasks defined 
in [Laravel](recipe/laravel.md) or in [common](recipe/common.md) recipes. To get 
list of all possible tasks let's run `dep` without any arguments:

```
$ dep
Available commands:
  deploy                     Deploy your project
  init                       Initialize deployer in your project
  rollback                   Rollback to previous release
  run                        Run any arbitrary command on hosts
  ssh                        Connect to host through ssh
  status                     Show releases status
  tree                       Display the task-tree for a given task
 artisan
  artisan:cache:clear        Flush the application cache
  ...
 deploy
  deploy:update_code         Updates code
  ...
```

Let's see what task defined in `deploy` task via `dep tree` command:

```
$ dep tree deploy
The task-tree for deploy:
└── deploy
    ├── deploy:prepare
    │   ├── deploy:info
    │   ├── deploy:setup
    │   ├── deploy:lock
    │   ├── deploy:release
    │   ├── deploy:update_code
    │   ├── deploy:shared
    │   └── deploy:writable
    ├── deploy:vendors
    ├── artisan:storage:link
    ├── artisan:view:cache
    ├── artisan:config:cache
    └── deploy:publish
        ├── deploy:symlink
        ├── deploy:unlock
        ├── deploy:cleanup
        └── deploy:success
```

We can override `deploy` task if we want to in our recipe:

```php
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'artisan:storage:link',
    'artisan:view:cache',
    'artisan:config:cache',
    'deploy:publish',
    'my_task',
]);
```

Or we can use hooks to add our own tasks:

```php
after('deploy', 'my_task');
```

Let's try to connect to host via `dep` command:

```bash
dep ssh
```

If everything went well we now can deploy our application:

```bash
$ dep deploy
[deploy.pw] info deploying HEAD
task deploy:setup
task deploy:lock
task deploy:release
task deploy:update_code
task deploy:shared
task deploy:writable
task deploy:vendors
task artisan:storage:link
task artisan:view:cache
task artisan:config:cache
task deploy:symlink
task deploy:unlock
task deploy:cleanup
[deploy.pw] info successfully deployed!
```


