# CLI Usage

We recommend adding next alias to your .bashrc file:

```bash
alias dep='vendor/bin/dep'
```

As well as installing completion script for Deployer, completion supports:

- tasks,
- options,
- host names,
- and configs.

For example for macOS run next commands:

```bash
brew install bash-completion
dep completion bash > /usr/local/etc/bash_completion.d/deployer
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

## Tree command

Deployer has group tasks and before/after hooks, so see task tree use **dep tree** command:

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
    │   ├── build  // after deploy:update_code
    │   ├── deploy:shared
    │   └── deploy:writable
    ├── deploy:vendors
    ├── artisan:storage:link
    ├── artisan:config:cache
    ├── artisan:route:cache
    ├── artisan:view:cache
    ├── artisan:migrate
    └── deploy:publish
        ├── deploy:symlink
        ├── deploy:unlock
        ├── deploy:cleanup
        └── deploy:success
```

## Execution plan

Before executing tasks, Deployer needs to flatten task tree and to decide in which order it will be executing tasks
on which hosts. Use `--plan` option to output table with tasks/hosts:

```
$ dep deploy --plan all
┌──────────────────────┬──────────────────────┬──────────────────────┬──────────────────────┐
│ prod01               │ prod02               │ prod03               │ prod04               │
├──────────────────────┼──────────────────────┼──────────────────────┼──────────────────────┤
│ deploy:info          │ deploy:info          │ deploy:info          │ deploy:info          │
│ deploy:setup         │ deploy:setup         │ deploy:setup         │ deploy:setup         │
│ deploy:lock          │ deploy:lock          │ deploy:lock          │ deploy:lock          │
│ deploy:release       │ deploy:release       │ deploy:release       │ deploy:release       │
│ deploy:update_code   │ deploy:update_code   │ deploy:update_code   │ deploy:update_code   │
│ build                │ build                │ build                │ build                │
│ deploy:shared        │ deploy:shared        │ deploy:shared        │ deploy:shared        │
│ deploy:writable      │ deploy:writable      │ deploy:writable      │ deploy:writable      │
│ deploy:vendors       │ deploy:vendors       │ deploy:vendors       │ deploy:vendors       │
│ artisan:storage:link │ artisan:storage:link │ artisan:storage:link │ artisan:storage:link │
│ artisan:config:cache │ artisan:config:cache │ artisan:config:cache │ artisan:config:cache │
│ artisan:route:cache  │ artisan:route:cache  │ artisan:route:cache  │ artisan:route:cache  │
│ artisan:view:cache   │ artisan:view:cache   │ artisan:view:cache   │ artisan:view:cache   │
│ artisan:migrate      │ artisan:migrate      │ artisan:migrate      │ artisan:migrate      │
│ deploy:symlink       │ -                    │ -                    │ -                    │
│ -                    │ deploy:symlink       │ -                    │ -                    │
│ -                    │ -                    │ deploy:symlink       │ -                    │
│ -                    │ -                    │ -                    │ deploy:symlink       │
│ deploy:unlock        │ deploy:unlock        │ deploy:unlock        │ deploy:unlock        │
│ deploy:cleanup       │ deploy:cleanup       │ deploy:cleanup       │ deploy:cleanup       │
│ deploy:success       │ deploy:success       │ deploy:success       │ deploy:success       │
└──────────────────────┴──────────────────────┴──────────────────────┴──────────────────────┘
```

The **deploy.php**:

```php
host('prod[01:04]');
task('deploy:symlink')->limit(1);
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

## Play blackjack

> Yeah, well. I'm gonna go build my own theme park... with blackjack and hookers!
>
> In fact, forget the park!
>
> — Bender

```
dep blackjack
```
