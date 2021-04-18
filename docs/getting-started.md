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
dep hello -v
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
and hosts define there to run it.
