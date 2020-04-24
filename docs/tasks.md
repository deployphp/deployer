# Tasks

Define your own tasks, by using the `task` function. Also, you can setup a description for a task with the `desc` function:

```php
desc('My task');
task('my_task', function () {
    run(...);
});
```

To run your task:

```sh
dep my_task
```

To list all available commands:

```sh
dep list
```

To run a task only on a specified host or stage:

```sh
dep deploy main
```

You can specify hosts via the `--hosts` option (comma separate multiple values) and roles via the `--roles` option:

```sh
dep deploy --hosts domain.com
dep deploy --roles app
```

### Simple tasks

If your task only contains `run` calls, or just one bash command, you can simplify the task definition:

```php
task('build', 'npm build');
```

> By default all simple tasks cd to `release_path`, so you don't need to.

Or you can use a multi line script:
 
```php
task('build', '
    gulp build;
    webpack -p;
    echo "Build done";
');
```

### Task grouping

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

### Before and after

You can define tasks to be run before or after some tasks.

``` php
task('deploy:done', function () {
    write('Deploy done!');
});

after('deploy', 'deploy:done');
```

After the `deploy` task is called, `deploy:done` will be executed.

### Filtering

You can specify on which hosts/stages/roles you want to run a task.

### By stage

Filter hosts by stage:

``` php
desc('Run tests for application');
task('test', function () {
    ...
})->onStage('test');
```

### By roles

Filter tasks by roles:

``` php
desc('Migrate database');
task('migrate', function () {
    ...
})->onRoles('db');
```

Also you can specify multiple roles: `onRoles('app', 'db', ...)`.

### By hosts

Filter tasks by hosts:

``` php
desc('Migrate database');
task('migrate', function () {
    ...
})->onHosts('db.domain.com');
```

Also you can specify multiple hosts: `onHosts('db.domain.com', ...)`.

### Local tasks

Mark a task with `local` to run it locally and only once, independent from the hosts count.

```php
task('build', function () {
    ...
})->local();
```

> Note that calling `run` inside a local task will have the same effect as calling `runLocally`. 

### Once

To run a task only once:

```php
task('do', ...)->once();
```

Will run on the first host only.

### Reconfigure

You can reconfigure tasks, e.g. those provided by 3rd party recipes by retrieving them by name:

```php
task('notify')->onStage('production');
```

### Overriding tasks

Some times you may want to have a different behavior of some task from the common recipes. Simply override it:

```php
task('deploy:update_code', function () {
    // Your custom update code
    upload(...);
});
```

### Using input options

You can define additional input options and arguments, before defining tasks:

``` php
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

argument('stage', InputArgument::OPTIONAL, 'Run tasks only on this host or stage.');
option('tag', null, InputOption::VALUE_OPTIONAL, 'Tag to deploy.');
```

To get the input inside a task, this can be used:

``` php
task('foo:bar', function() {
    // For arguments
    $stage = null;
    if (input()->hasArgument('stage')) {
        $stage = input()->getArgument('stage');
    }
    
    // For option
    $tag = null;
    if (input()->hasOption('tag')) {
        $tag = input()->getOption('tag');
    }
});
```

### Parallel task execution

When deploying to multiple hosts, Deployer will run one task on each host:

<svg width="600" height="350" viewBox="0 0 600 350" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><g transform="translate(456 309)"><rect fill="#EBEBEB" width="140" height="37.176" rx="8"/><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="42" y="24">task 2</tspan></text></g><g transform="translate(306 271)"><rect fill="#EBEBEB" width="140" height="37.176" rx="8"/><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="42" y="24">task 2</tspan></text></g><g transform="translate(156 233)"><rect fill="#EBEBEB" width="140" height="37.176" rx="8"/><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="42" y="24">task 2</tspan></text></g><g transform="translate(6 195)"><rect fill="#EBEBEB" width="140" height="37.176" rx="8"/><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="43" y="24">task 2</tspan></text></g><g transform="translate(456 157)"><rect fill="#EBEBEB" width="140" height="37.176" rx="8"/><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="41" y="24">task 1</tspan></text></g><g transform="translate(306 119)"><rect fill="#EBEBEB" width="140" height="37.176" rx="8"/><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="41" y="24">task 1</tspan></text></g><g transform="translate(156 81)"><rect fill="#EBEBEB" width="140" height="37.176" rx="8"/><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="41" y="24">task 1</tspan></text></g><g transform="translate(6 43)"><rect fill="#EBEBEB" width="140" height="37.176" rx="8"/><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="41" y="24">task 1</tspan></text></g><path d="M3 35h594.5" stroke="#EBEBEB" stroke-linecap="square" stroke-dasharray="3,5"/><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="497" y="25">Host 4</tspan></text><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="347" y="25">Host 3</tspan></text><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="197" y="25">Host 2</tspan></text><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="47" y="25">Host 1</tspan></text></g></svg>

To speedup deployment add the `--parallel` or `-p` option. This will run tasks in parallel on each host. If execution of the task on a host takes longer then on others, Deployer will wait until all hosts have finished their tasks.

<svg width="600" height="153" viewBox="0 0 600 153" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><g transform="translate(456 91)"><rect fill="#EBEBEB" width="140" height="37.176" rx="8"/><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="42" y="24">task 2</tspan></text></g><g transform="translate(306 91)"><rect fill="#EBEBEB" width="140" height="37.176" rx="8"/><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="42" y="24">task 2</tspan></text></g><g transform="translate(156 91)"><rect fill="#EBEBEB" width="140" height="37.176" rx="8"/><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="42" y="24">task 2</tspan></text></g><g transform="translate(6 91)"><rect fill="#EBEBEB" width="140" height="37.176" rx="8"/><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="43" y="24">task 2</tspan></text></g><g transform="translate(456 43)"><rect fill="#EBEBEB" width="140" height="37.176" rx="8"/><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="41" y="24">task 1</tspan></text></g><g transform="translate(306 43)"><rect fill="#EBEBEB" width="140" height="37.176" rx="8"/><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="41" y="24">task 1</tspan></text></g><g transform="translate(156 43)"><rect fill="#EBEBEB" width="140" height="37.176" rx="8"/><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="41" y="24">task 1</tspan></text></g><g transform="translate(6 43)"><rect fill="#EBEBEB" width="140" height="37.176" rx="8"/><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="41" y="24">task 1</tspan></text></g><path d="M3 35h594.5" stroke="#EBEBEB" stroke-linecap="square" stroke-dasharray="3,5"/><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="497" y="25">Host 4</tspan></text><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="347" y="25">Host 3</tspan></text><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="197" y="25">Host 2</tspan></text><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="47" y="25">Host 1</tspan></text></g></svg>

Limit the number of concurrent tasks by specifying a number. By default, up to 10 tasks will be processed concurrently.
  
```sh
dep deploy --parallel --limit 2
```

<svg width="600" height="210" viewBox="0 0 600 210" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><g transform="translate(456 157)"><rect fill="#EBEBEB" width="140" height="37.176" rx="8"/><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="42" y="24">task 2</tspan></text></g><g transform="translate(306 157)"><rect fill="#EBEBEB" width="140" height="37.176" rx="8"/><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="42" y="24">task 2</tspan></text></g><g transform="translate(156 119)"><rect fill="#EBEBEB" width="140" height="37.176" rx="8"/><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="42" y="24">task 2</tspan></text></g><g transform="translate(6 119)"><rect fill="#EBEBEB" width="140" height="37.176" rx="8"/><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="43" y="24">task 2</tspan></text></g><g transform="translate(456 81)"><rect fill="#EBEBEB" width="140" height="37.176" rx="8"/><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="41" y="24">task 1</tspan></text></g><g transform="translate(306 81)"><rect fill="#EBEBEB" width="140" height="37.176" rx="8"/><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="41" y="24">task 1</tspan></text></g><g transform="translate(156 43)"><rect fill="#EBEBEB" width="140" height="37.176" rx="8"/><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="41" y="24">task 1</tspan></text></g><g transform="translate(6 43)"><rect fill="#EBEBEB" width="140" height="37.176" rx="8"/><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="41" y="24">task 1</tspan></text></g><path d="M3 35h594.5" stroke="#EBEBEB" stroke-linecap="square" stroke-dasharray="3,5"/><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="497" y="25">Host 4</tspan></text><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="347" y="25">Host 3</tspan></text><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="197" y="25">Host 2</tspan></text><text font-family="Monaco" font-size="16" fill="#9B9B9B"><tspan x="47" y="25">Host 1</tspan></text></g></svg>

Next: [hosts](hosts.md).
