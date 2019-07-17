# API Reference

### host

* `host(string ...$hostname): Host`

Define a host or group of hosts. Read more at [hosts](hosts.md).

### localhost

* `localhost(string ...$alias = 'localhost'): Host`

Define a localhost.

### inventory

* `inventory(string $file): Host[]`

Load a list of hosts from a file.

### desc

* `desc(string $description)`

Set a task description.

### task

* `task(string $name, string $script)`
* `task(string $name, callable $callable)`
* `task(string $name): Task`

Define a task or get a task. More at [tasks](tasks.md).

### before

* `before(string $when, string $that)`

Call before `$when` task, `$that` task.

### after

* `after(string $when, string $that)`

Call after `$when` task, `$that` task.

### fail

* `fail(string $what, string $that)`

If task `$what` fails, run `$that` task.

### argument

* `argument($name, $mode = null, $description = '', $default = null)`

Add user's cli arguments.

### option

* `option($name, $shortcut=null, $mode=null, $description='', $default=null)`

Add user's cli options.

### cd

* `cd(string $path)`

Sets the working path for the following `run` functions. 
Every task restores the working path to the base working path at the beginning of the task.

~~~php
cd('{{release_path}}');
run('npm run build');
~~~

### within

* `within(string $path, callable $callback)`

Run `$callback` within `$path`.

~~~php
within('{{release_path}}', function () {
    run('npm run build');   
});
~~~

### workingPath

* `workingPath(): string`

Return the current working path.

~~~php
cd('{{release_path}}');
workingPath() == '/var/www/app/releases/1';
~~~

### run

* `run(string $command, $options = []): string`

Run a command on remote host. Available options:

* `timeout` — Sets the process timeout (max. runtime).  
  To disable the timeout, set this value to null.  
  The timeout in seconds (default: 300 sec)
* `tty` — Enables or disables the TTY mode (default: false)

For example, if your private key contains a passphrase, enable tty and you'll see git prompt for a password. 

~~~php
run('git clone ...', ['timeout' => null, 'tty' => true]);
~~~

`run` function returns the output of the command as a string:
   
~~~php
$path = run('readlink {{deploy_path}}/current');
run("echo $path");
~~~

### runLocally

* `runLocally($command, $options = []): string`

Run a command on localhost. Available options:

* `timeout` — The timeout in seconds (default: 300 sec)
* `tty` — The TTY mode (default: false)

### test

* `test(string $command): bool`

Run a test command.
 
~~~php
if (test('[ -d {{release_path}} ]')) {
    ...
}
~~~

### testLocally

* `testLocally(string $command): bool`

Run a test command locally.

### on

* `on(Host $host, callable $callback)`
* `on(Host[] $host, callable $callback)`

Execute a `$callback` on the specified hosts.

~~~php
on(host('domain.com'), function ($host) {
   ...
});
~~~

~~~php
on(roles('app'), function ($host) {
   ...
});
~~~

~~~php
on(Deployer::get()->hosts, function ($host) {
   ...
});
~~~

### roles

* `roles(string ...$role): Host[]`

Return a list of hosts by roles.

### invoke

* `invoke(string $task)`

Run a task on the current host. 

~~~php
task('deploy', function () {
    invoke('deploy:prepare'); 
    invoke('deploy:release');
    ...
});
~~~

> **Note** this is experimental functionality.

### upload

* `upload(string $source, string $destination, $config = [])`

Upload files from `$source` to `$destination` on the remote host.

~~~php
upload('build/', '{{release_path}}/public');
~~~

> You may have noticed that there is a trailing slash (/) at the end of the first argument in the above command, 
> this is necessary to mean "the contents of `build`".
>
> The alternative, without the trailing slash, would place `build`, including the directory, within `public`. 
> This would create a hierarchy that looks like: `{{release_path}}/public/build`

Available options:

* `timeout` — The timeout in seconds (default: null)
* `options` — `rsync` options.

### download

* `download(string $source, string $destination, $config = [])`

Download files from the remote host `$source` to `$destination` on the local machine.

Available options:

* `timeout` — The timeout in seconds (default: null)
* `options` — `rsync` options.

### write

Write a message in the output. 
You can format the message with the tags `<info>...</info>`, `<comment></comment>` or `<error></error>` (see [Symfony Console](http://symfony.com/doc/current/console/coloring.html)).

### writeln

Same as the `write` function, but also writes a new line.

### set

* `set(string $name, string|int|bool|array $value)`
* `set(string $name, callable $value)`

Setup a global configuration parameter. If callable is passed as `$value` it will be triggered on the first get of this config.

More at [configuration](configuration.md).

### add

* `add(string $name, array $values)`

Add values to already existing config. 

More at [configuration](configuration.md).

### get

* `get(string $name, $default = null): string|int|bool|array`

Get a configuration value.

More at [configuration](configuration.md).

### has

* `has(string $name): bool`

Check if a config option exists.

More at [configuration](configuration.md).

### ask

* `ask(string $message, $default = null, $suggestedChoices = null)`

Ask the user for input.

### askChoice

* `askChoice(string $message, array $availableChoices, $default = null, $multiselect = false)`

Ask the user to select from multiple key/value options and return an array. 
Multiselect enables selection of multiple comma separated choices. 
The default value will be used in quiet mode, otherwise the first available choice will be accepted.

### askConfirmation

* `askConfirmation(string $message, bool $default = false)`

Ask the user a yes or no question.

### askHiddenResponse

* `askHiddenResponse(string $message)`

Ask the user for a password.

### input

* `input(): Input`

Get the current console input.

### output

* `output(): Output`

Get the current console output.

### isQuiet

* `isQuiet(): bool`

Check if th `dep` command was started with the `-q` option.

### isVerbose

* `isVerbose(): bool`

Check if the `dep` command was started with the `-v` option.

### isVeryVerbose

* `isVeryVerbose(): bool`

Check if th `dep` command was started with the `-vv` option.

### isDebug

* `isDebug(): bool`

Check if the `dep` command was started with the `-vvv` option.


### commandExist

* `commandExist(string $command): bool`

Check if a command exists.

~~~php
if (commandExist('composer')) {
    ...
}
~~~

### parse

* `parse(string $line): string`

Parse config occurrence `{{` `}}` in `$line`.
