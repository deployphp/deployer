# CLI Usage

After [installation](installation.md) of Deployer you will have the ability to run the `dep` command from your terminal.

To get list of all available tasks run the `dep` command. You can run it from any subdirectories of you project; 
Deployer will automatically find project root dir. 
 
~~~bash
Deployer

Usage:
  command [options] [arguments]

Options:
  -h, --help            Display this help message
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi            Force ANSI output
      --no-ansi         Disable ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -f, --file[=FILE]     Specify Deployer file
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Available commands:
  help         Displays help for a command
  init         Initialize deployer system in your project
  list         Lists commands
  run          Run any arbitrary command on hosts
  self-update  Updates deployer.phar to the latest version
  ssh          Connect to host through ssh
~~~

The best way to configure your `deploy.php` is to automatically deploy to staging on this command:
 
~~~bash
dep deploy
~~~

This is so somebody can't accidentally deploy to production (for production deployment, the `dep deploy production` command explicitly lists the required production stage).

You need info about available options and usage use the `help` command:
 
~~~bash
$ dep help deploy
Usage:
  deploy [options] [--] [<stage>]

Arguments:
  stage                      Stage or hostname

Options:
  -p, --parallel             Run tasks in parallel
  -l, --limit=LIMIT          How many host to run in parallel?
      --no-hooks             Run task without after/before hooks
      --log=LOG              Log to file
      --roles=ROLES          Roles to deploy
      --hosts=HOSTS          Host to deploy, comma separated, supports ranges [:]
  -o, --option=OPTION        Sets configuration option (multiple values allowed)
  -h, --help                 Display this help message
  -q, --quiet                Do not output any message
  -V, --version              Display this application version
      --ansi                 Force ANSI output
      --no-ansi              Disable ANSI output
  -n, --no-interaction       Do not ask any interactive question
  -f, --file[=FILE]          Specify Deployer file
      --tag[=TAG]            Tag to deploy
      --revision[=REVISION]  Revision to deploy
      --branch[=BRANCH]      Branch to deploy
  -v|vv|vvv, --verbose       Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
 
Help:
  Deploy your project
~~~

### Overriding configuration options

For example, if your _deploy.php_ file contains this configuration:

~~~php
set('ssh_multiplexing', false);
~~~

And you want to enable [ssh multiplexing](https://en.wikibooks.org/wiki/OpenSSH/Cookbook/Multiplexing) without modifying the file, you can pass the `-o` option to the `dep` command:

~~~bash
dep deploy -o ssh_multiplexing=true
~~~

To override multiple config options, you can pass multiple `-o` args:

~~~bash
dep deploy -o ssh_multiplexing=true -o branch=master
~~~

### Running arbitrary commands
 
Deployer comes with a command to run any valid command on you server without modifying _deploy.php_
 
~~~bash
dep run 'ls -la'
~~~

To specify the hosts this command has the corresponding options:

~~~
  --stage=STAGE    Stage to deploy
  --roles=ROLES    Roles to deploy
  --hosts=HOSTS    Host to deploy, comma separated, supports ranges [:]
~~~

### Getting help

You can get more info about any commands by using the help command:

~~~
dep help [command]
~~~

### Autocomplete

Deployer comes with an autocomplete script for bash/zsh/fish, so you don't need to remember all the tasks and options.
To install it run following command:

~~~bash
dep autocomplete
~~~

And follow instructions.
