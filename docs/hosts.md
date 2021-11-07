# Hosts

To define a new host use [host()](api.md#host) function. Deployer keeps list of
all defined tasks in the `Deployer::get()->hosts` collection.

```php
host('example.org');
```

Each host contains own configuration key-value pairs. The [host()](api.md#host)
call defines two important configs: **alias** and **hostname**.

- **hostname** - used then connecting to remote host.
- **alias** - used as a key in `Deployer::get()->hosts` collection.

```php
task('test', function () {
    writeln('The {{alias}} is {{hostname}}');
});
```

```bash
$ dep test
[example.org] The example.org is example.org
```

We can override hostname via `set()` method:

```php
host('example.org')
    ->set('hostname', 'example.cloud.google.com');
```

Now new hostname will be used for ssh connect, and host will be referred in
Deployer via the alias.

```bash
$ dep test
[example.org] The example.org is example.cloud.google.com
```

Another important ssh connection parameter is `remote_user`.

```php
host('example.org')
    ->set('hostname', 'example.cloud.google.com');
    ->set('remote_user', 'deployer');
```

Now Deployer will be using something
like `ssh deployer@example.cloud.google.com`
for establishing connection.

Also, Deployer's `Host` class has special setter methods (for better IDE
autocompletion).

```php
host('example.org')
    ->setHostname('example.cloud.google.com');
    ->setRemoteUser('deployer');
```

:::info Config file
It is a good practice to keep connection parameters out of `deploy.php` file, as
they can change depending on where are deploy executed. Only specify `hostname`
and `remote_user` and other keep in `~/.ssh/config`:

```
Host *
  IdentityFile ~/.ssh/id_rsa
```
:::

## Host config

|Method|Value|
|------|-----|
|`setHostname` | The `hostname` |
|`setRemoteUser` | The `remote_user` |
|`setPort` | The `port` |
|`setConfigFile` | For example, `~/.ssh/config`. |
|`setIdentityFile` | For example, `~/.ssh/id_rsa`. |
|`setForwardAgent` | Default: `true`. |
|`setSshMultiplexing` | Default: `true`. |
|`setShell` | Default: `bash -ls`. |
|`setDeployPath` | For example, `~/myapp`. |
|`setLabels` | Key-value pairs for host selector. |
|`setSshArguments` | For example, `['-o UserKnownHostsFile=/dev/null']` |

