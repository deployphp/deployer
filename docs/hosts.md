# Hosts

To define a new host use the [host()](api.md#host) function. Deployer keeps a list of
all defined hosts in the `Deployer::get()->hosts` collection.

```php
host('example.org');
```

Each host contains it's own configuration key-value pairs. The [host()](api.md#host)
call defines two important configs: **alias** and **hostname**.

- **hostname** - used when connecting to remote host.
- **alias** - used as a key in `Deployer::get()->hosts` collection.

```php
task('test', function () {
    writeln('The {{alias}} is {{hostname}}');
});
```

```
$ dep test
[example.org] The example.org is example.org
```

We can override hostname via `set()` method:

```php
host('example.org')
    ->set('hostname', 'example.cloud.google.com');
```

The hostname will be used for the ssh connection, but the host will be referred
by its alias when running Deployer.

```
$ dep test
[example.org] The example.org is example.cloud.google.com
```

Another important ssh connection parameter is `remote_user`.

```php
host('example.org')
    ->set('hostname', 'example.cloud.google.com')
    ->set('remote_user', 'deployer');
```

Now Deployer will connect using something like
`ssh deployer@example.cloud.google.com` to establishing connection.

Also, Deployer's `Host` class has special setter methods (for better IDE
autocompletion).

```php
host('example.org')
    ->setHostname('example.cloud.google.com')
    ->setRemoteUser('deployer');
```

## Host labels

Hosts can receive labels to identify a subselection of all available hosts. This is a flexible approach to manage and deploy multiple hosts.
The label names and values can be chosen freely. For example, a stage name can be applied: 

```php
host('example.org')
    ->setLabels(['stage' => 'prod'])
;

host('staging.example.org')
    ->setLabels(['stage' => 'staging'])
;

```
The example above can be simplified without labels, by giving the host prod and staging as name, and using setHostname(...). 

But for for multi server setups, labels become much more powerful:

```php
host('admin.example.org')
    ->setLabels(['stage' => 'prod', 'role' => 'web'])
;

host('web[1:5].example.org')
    ->setLabels(['stage' => 'prod', 'role' => 'web'])
;

host('db[1:2].example.org')
    ->setLabels(['stage' => 'prod', 'role' => 'db'])
;

host('test.example.org')
    ->setLabels(['stage' => 'test', 'role' => 'web'])
;

host('special.example.org')
    ->setLabels(['role' => 'special'])
;
```

When calling `dep deploy`, you can filter the hosts to deploy by passing a select string:

```
$ dep deploy stage=prod&role=web,role=special
```

To check for multiple labels that have to be set on the same host, you can use the `&` operator.
To add another selection, you can use `,` as a separator.

Also you can configure a default selection string, that is used when running 'dep deploy' without arguments.

```php
set('default_selector', "stage=prod&role=web,role=special");
```


## Host config

### `alias`

The identifier used to identify a host.
You can use actual hostname or something like `prod` or `staging`.

### `hostname`

Deployer uses this config for actual ssh connection.

### `remote_user`

Deployer uses this config for actual ssh connection. If not specified,
Deployer will be using `RemoteUser` from **~/.ssh/config** file, or current
OS username.

### `port`

Port of remote ssh server to connect to. Default is `22`.

### `config_file`

Default is `~/.ssh/config`.

:::info Config file
For best practices, avoid storing connection parameters in the `deploy.php` file, as 
these can vary based on the deployment execution location. Instead, only include the 
hostname and remote_user in `deploy.php`, while maintaining other parameters in the
`~/.ssh/config` file.

```
Host *
  IdentityFile ~/.ssh/id_rsa
```

:::

### `identity_file`

For example, `~/.ssh/id_rsa`.

### `forward_agent`

SSH forwarding is a way to securely tunnel network connections from your local computer to a remote server, and from the remote server to another destination. There are several types of SSH forwarding, including local, remote, and dynamic forwarding. SSH agent forwarding is a specific type of local forwarding that allows you to use your local SSH keys to authenticate on remote servers. This can be useful if you want to use your local SSH keys to connect to a remote server, but don't want to copy your keys to the remote server.

Default is `true`.

### `ssh_multiplexing`

SSH multiplexing is a technique that allows a single Secure Shell (SSH) connection to be used for multiple interactive sessions or for multiple tunneled connections. This can be useful in a number of situations, such as when you want to open multiple terminal sessions to a remote server over a single SSH connection, or when you want to establish multiple secure connections to a remote server but don't want to open multiple SSH connections.

Default is `true`.

### `shell`

Default is `bash -ls`.

### `deploy_path`

For example, `~/myapp`.

### `labels`

Key-value pairs for host selector.

### `ssh_arguments`

For example, `['-o UserKnownHostsFile=/dev/null']`

### `ssh_control_path`

Default is `~/.ssh/%C`.

If **CI** env is present, default value is `/dev/shm/%C`.

## Multiple hosts

You can pass multiple hosts to the host function:

```php
host('example.org', 'deployer.org', ...)
    ->setRemoteUser('anton');
```

## Host ranges

If you have a lot of hosts following similar patterns, you can describe them
like this rather than listing each hostname:

```php
host('www[01:50].example.org');
```

For numeric patterns, leading zeros can be included or removed, as desired.
Ranges are inclusive.

You can also define alphabetic ranges:

```php
host('db[a:f].example.org');
```

## Localhost

The [localhost()](api.md#localhost) function defines a special local host.
Deployer will not connect to this host, but will execute commands locally instead.

```php
localhost(); // Alias and hostname will be "localhost".
localhost('ci'); // Alias is "ci", hostname is "localhost".
```

## YAML Inventory

You can use the [import()](api.md#import) function to keep host definitions in a
separate file. For example, _inventory.yaml_.

```php title="deploy.php"
import('inventory.yaml');
```

```yaml title="inventory.yaml"
hosts:
  example.org:
    remote_user: deployer
  deployer.org:
    remote_user: deployer
```
