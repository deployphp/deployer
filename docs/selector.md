# Selector

Deployer uses the selector to choose hosts. Each host can have a set of labels. 
Labels are key-value pairs. 

For example, `stage: production` or `role: web`. 

You can use labels to select hosts. For example, `dep deploy stage=production` 
will deploy to all hosts with `stage: production` label.

Let's define two labels **type** and **env** of our hosts:

```php
host('web.example.com')
    ->setLabels([
        'type' => 'web',
        'env' => 'prod',
    ]);

host('db.example.com')
    ->setLabels([
        'type' => 'db',
        'env' => 'prod',
    ]);
```

Now let's define a task to check labels:

```php
task('info', function () {
    writeln('type:' . get('labels')['type'] . ' env:' . get('labels')['env']);
});
```

Now we can run this task on with a selector:

```bash
$ dep info env=prod
task info
[web.example.com] type:web env:prod
[db.example.com] type:db env:prod
```

As you can see, Deployer will run this task on all hosts with `env: prod` label.
And if we define only `type` label, Deployer will run this task on specified host.

```bash
dep info type=web
task info
[web.example.com] type:web env:prod
```

## Selector syntax

Label syntax is represented by [disjunctive normal form](https://en.wikipedia.org/wiki/Disjunctive_normal_form) 
(**OR of ANDs**).

For example, `type=web,env=prod` is a selector of: `type=web` **OR** `env=prod`.

```bash
$ dep info 'type=web,env=prod'
task info
[web.example.com] type:web env:prod
[db.example.com] type:db env:prod
```

As you can see both hosts are selected (as both of them has `env: prod` label).

We can use `&` to define **AND**. For example, `type=web & env=prod` is a selector
for hosts with `type: web` **AND** `env: prod` labels.

```bash
$ dep info 'type=web & env=prod'
task info
[web.example.com] type:web env:prod
```

We can also use `!=` to negate a label. For example, `type!=web` is a selector for
all hosts what has not `type: web` label.

```bash
$ dep info 'type!=web'
task info
[db.example.com] type:db env:prod
```

:::note 
Deployer CLI can take a few selectors as arguments. For example, 
`dep info type=web env=prod` is a same as `dep info 'type=web,env=prod'`.

You can install bash autocompletion for Deployer CLI, which will help you to
write selectors. See [installation](installation.md) for more.
:::

Deployer also has a few special selectors:

- `all` - select all hosts
- `alias=...` - select host by alias

If a selector does not contain `=` sign, Deployer will assume that it is an alias.

For example `dep info web.example.com` is a same as `dep info alias=web.example.com`.

```bash
$ dep info web.example.com
task info
[web.example.com] type:web env:prod
```

```bash
$ dep info 'web.example.com' 'db.example.com'
$ # Same as: 
$ dep info 'alias=web.example.com,alias=db.example.com'
````

## Using select() function

You can use [select()](api.md#select) function to select hosts by selector from PHP code.

```php
task('info', function () {
    $hosts = select('type=web,env=prod');
    foreach ($hosts as $host) {
        writeln('type:' . $host->get('labels')['type'] . ' env:' . $host->get('labels')['env']);
    }
});
```

Or you can use [on()](api.md#on) function to run a task on selected hosts.

```php
task('info', function () {
    on(select('all'), function () {
        writeln('type:' . get('labels')['type'] . ' env:' . get('labels')['env']);
    });
});
```

## Task selectors

To restrict a task to run only on selected hosts, you can use [select()](tasks.md#select) method.

```php
task('info', function () {
    // ...
})->select('type=web,env=prod');
```

## Labels in YAML

You can also define labels in YAML recipe. For example:

```yaml
hosts:
  web.example.com:
    remote_user: deployer
    env: production
    labels:
      env: prod
```

But make sure to distinguish between `env` and `labels.env` keys.
`env` is a configuration key, and `labels.env` is a label.

```php
task('info', function () {
    writeln('env:' . get('env') . ' labels.env:' . get('labels')['env']);
});
```

Will print:

```bash
$ dep info env=prod
task info
[web.example.com] env:production labels.env:prod
```
