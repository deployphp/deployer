# Selector

Deployer uses the selector to choose hosts. Each host can have a set of labels. 
Labels are key-value pairs. 

For example, `stage: production` or `role: web`. 

You can use labels to select hosts. For example, `dep deploy stage=production` 
will deploy to all hosts with `stage: production` label.

Let's define two labels, **type** and **env**, to our hosts:

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
or use `->addLables()` method to add labels to the existing host.

Now let's define a task to check labels:

```php
task('info', function () {
    writeln('type:' . get('labels')['type'] . ' env:' . get('labels')['env']);
});
```

Now we can run this task with a selector:

```bash
$ dep info env=prod
task info
[web.example.com] type:web env:prod
[db.example.com] type:db env:prod
```

As you can see, Deployer will run this task on all hosts with the `env: prod` label.
And if we define only the `type` label, Deployer will run this task on the specified host.

```bash
dep info type=web
task info
[web.example.com] type:web env:prod
```

## Selector syntax

Label syntax is represented by [disjunctive normal form](https://en.wikipedia.org/wiki/Disjunctive_normal_form) 
(**OR of ANDs**).
```
(condition1 AND condition2) OR (condition3 AND condition4)
```

Each condition in the subquery that is represented by [conjunctive normal form](https://en.wikipedia.org/wiki/Conjunctive_normal_form)
```
(condition1 OR condition2) AND (condition3 OR condition4)
```

### Explanation

For example, `type=web,env=prod` is a selector of: `type=web` **OR** `env=prod`.

```bash
$ dep info 'type=web,env=prod'
task info
[web.example.com] type:web env:prod
[db.example.com] type:db env:prod
```

As you can see, both hosts are selected (as both of them have the `env: prod` label).

We can use `&` to define **AND**. For example, `type=web & env=prod` is a selector
for hosts with `type: web` **AND** `env: prod` labels.

```bash
$ dep info 'type=web & env=prod'
task info
[web.example.com] type:web env:prod
```

We can use `|` to define **OR** in a subquery. For example, `type=web|db & env=prod` is a selector
for hosts with (`type: web` **OR** `type: db`) **AND** `env: prod` labels.

```bash
$ dep info 'type=web|db & env=prod'
task info
[web.example.com] type:web env:prod
```

We can also use `!=` to negate a label. For example, `type!=web` is a selector for
all hosts which do not have a `type: web` label.

```bash
$ dep info 'type!=web'
task info
[db.example.com] type:db env:prod
```

:::note 
Deployer CLI can take a few selectors as arguments. For example, 
`dep info type=web env=prod` is the same as `dep info 'type=web,env=prod'`.

You can install bash autocompletion for Deployer CLI, which will help you to
write selectors. See [installation](installation.md) for more.
:::

Deployer also has a few special selectors:

- `all` - select all hosts
- `alias=...` - select host by alias

If a selector does not contain an `=` sign, Deployer will assume that it is an alias.

For example `dep info web.example.com` is the same as `dep info alias=web.example.com`.

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

## Using the select() function

You can use the [select()](api.md#select) function to select hosts by selector in your PHP code.

```php
task('info', function () {
    $hosts = select('type=web|db,env=prod');
    foreach ($hosts as $host) {
        writeln('type:' . $host->get('labels')['type'] . ' env:' . $host->get('labels')['env']);
    }
});
```

Or you can use the [on()](api.md#on) function to run a task on selected hosts.

```php
task('info', function () {
    on(select('all'), function () {
        writeln('type:' . get('labels')['type'] . ' env:' . get('labels')['env']);
    });
});
```

## Task selectors

To restrict a task to run only on selected hosts, you can use the [select()](tasks.md#select) method.

```php
task('info', function () {
    // ...
})->select('type=web|db,env=prod');
```

## Labels in YAML

You can also define labels in a YAML recipe. For example:

```yaml
hosts:
  web.example.com:
    remote_user: deployer
    env:
      environment: production
    labels:
      env: prod
```

But make sure to distinguish between the `env` and `labels.env` keys.
`env` is a configuration key, and `labels.env` is a label.

```php
task('info', function () {
    writeln('env:' . get('env')['environment'] . ' labels.env:' . get('labels')['env']);
});
```

Will print:

```bash
$ dep info env=prod
task info
[web.example.com] env:production labels.env:prod
```
