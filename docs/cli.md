# CLI Usage

After [installation](installation.md) of Deployer globally, 
you will have the ability to run the `dep` command from your terminal.

To get a list of all available tasks run the `dep` command. 
You can run it from any subdirectory of you project,
Deployer will automatically find project root dir.

```
Usage:
  command [options] [arguments]

Options:
  -h, --help            Display help for the given command. When no command is given display help for the list command
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi|--no-ansi  Force (or disable --no-ansi) ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -f, --file=FILE       Recipe file path
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Available commands:
  autocomplete  Add CLI autocomplete
  blackjack     Play blackjack
  config        Get all configuration options for hosts
  help          Display help for a command
  init          Initialize deployer in your project
  list          List commands
  run           Run any arbitrary command on hosts
  ssh           Connect to host through ssh
  tree          Display the task-tree for a given task
```

## Overriding configuration options

For example, if your _deploy.php_ file contains this configuration:

```php
set('ssh_multiplexing', false);
```

And you want to enable [ssh multiplexing](https://en.wikibooks.org/wiki/OpenSSH/Cookbook/Multiplexing) without modifying the recipe, you can pass the `-o` option to the `dep` command:

```
dep deploy -o ssh_multiplexing=true
```

To override multiple config options, you can pass multiple `-o` args:

```
dep deploy -o ssh_multiplexing=true -o branch=master
```

## Running arbitrary commands

Run any command on one or more hosts:

```
dep run 'uptime -p'
```

## Getting help

You can get more info about any commands by using the help command:

```
dep help [command]
```

## The `runLocally` working dir

By default `runLocally()` commands are executed relative to the recipe file directory. 
This can be overridden globally by setting an environment variable:

```
DEPLOYER_ROOT=. dep taskname`
```

Alternatively the root directory can be overridden per command via the cwd configuration.

```php
runLocally('ls', ['cwd' => '/root/directory']);
```
