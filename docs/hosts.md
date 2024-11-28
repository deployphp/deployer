# Hosts

In Deployer, you define hosts using the [host()](api.md#host) function.

### Defining a Host

```php
host('example.org');
```

Each host is associated with configuration key-value pairs. When you define a host, two key configurations are set:

- **`hostname`**: Used for connecting to the remote host.
- **`alias`**: A unique identifier for the host in recipe.

### Example: Using Host Configurations

You can access host configurations within tasks with the [currentHost()](api.md#currenthost) function:

```php
task('test', function () {
    $hostname = currentHost()->get('hostname');
    $alias = currentHost()->get('alias');
    writeln("The $alias is $hostname");
});
```

Or using brackets syntax:

```php
task('test', function () {
    writeln('The {{alias}} is {{hostname}}');
});
```

Running the task:

```sh
$ dep test
[example.org] The example.org is example.org
```

### Overriding Hostname

You can override the default hostname with the `set()` method:

```php
host('example.org')
    ->set('hostname', 'example.cloud.google.com');
```

Now the `hostname` is used for SSH connections, but the `alias` remains unchanged:

```sh
$ dep test
[example.org] The example.org is example.cloud.google.com
```

### Configuring Remote User

Specify the `remote_user` to define which user to connect as:

```php
host('example.org')
    ->set('hostname', 'example.cloud.google.com')
    ->set('remote_user', 'deployer');
```

Deployer will now connect using `ssh deployer@example.cloud.google.com`.

Alternatively, you can use special setter methods for better IDE autocompletion:

```php
host('example.org')
    ->setHostname('example.cloud.google.com')
    ->setRemoteUser('deployer');
```

---

## Host Labels

Labels allow you to group and identify hosts for specific deployments. Labels are defined as key-value pairs:

```php
host('example.org')->setLabels(['stage' => 'prod']);
host('staging.example.org')->setLabels(['stage' => 'staging']);
```

Labels become powerful in multi-server setups:

```php
host('admin.example.org')->setLabels(['stage' => 'prod', 'role' => 'web']);
host('web[1:5].example.org')->setLabels(['stage' => 'prod', 'role' => 'web']);
host('db[1:2].example.org')->setLabels(['stage' => 'prod', 'role' => 'db']);
host('test.example.org')->setLabels(['stage' => 'test', 'role' => 'web']);
host('special.example.org')->setLabels(['role' => 'special']);
```

### Filtering Hosts by Labels

When deploying, you can filter hosts using label selectors:

```sh
$ dep deploy stage=prod&role=web,role=special
```

- Use `&` to specify multiple labels that must match on the same host.
- Use `,` to separate multiple selections.

Set a default selection string for convenience:

```php
set('default_selector', "stage=prod&role=web,role=special");
```

---

## Host Configurations

### Key Host Configurations

| Config Key             | Description                                                                                    |
|------------------------|------------------------------------------------------------------------------------------------|
| **`alias`**            | Identifier for the host (e.g., `prod`, `staging`).                                             |
| **`hostname`**         | Actual hostname or IP address used for SSH connections.                                        |
| **`remote_user`**      | SSH username. Defaults to the current OS user or `~/.ssh/config`.                              |
| **`port`**             | SSH port. Default is `22`.                                                                     |
| **`config_file`**      | SSH config file location. Default is `~/.ssh/config`.                                          |
| **`identity_file`**    | SSH private key file. E.g., `~/.ssh/id_rsa`.                                                   |
| **`forward_agent`**    | Enable SSH agent forwarding. Default is `true`.                                                |
| **`ssh_multiplexing`** | Enable SSH multiplexing for performance. Default is `true`.                                    |
| **`shell`**            | Shell to use. Default is `bash -ls`.                                                           |
| **`deploy_path`**      | Directory for deployments. E.g., `~/myapp`.                                                    |
| **`labels`**           | Key-value pairs for host selection.                                                            |
| **`ssh_arguments`**    | Additional SSH options. E.g., `['-o UserKnownHostsFile=/dev/null']`.                           |
| **`ssh_control_path`** | Control path for SSH multiplexing. Default is `~/.ssh/%C` or `/dev/shm/%C` in CI environments. |

### Best Practices

Avoid storing sensitive SSH connection parameters in `deploy.php`. Instead, configure them in `~/.ssh/config`:

```
Host *
  IdentityFile ~/.ssh/id_rsa
```

---

## Advanced Host Definitions

### Multiple Hosts

Define multiple hosts in one call:

```php
host('example.org', 'deployer.org', 'another.org')->setRemoteUser('anton');
```

### Host Ranges

For patterns with many hosts, use ranges:

```php
host('www[01:50].example.org'); // Will define hosts "www01.example.org", "www02.example.org", etc.
host('db[a:f].example.org'); // Will define hosts "dba.example.org", "dbb.example.org", etc.
```

- Numeric ranges can include leading zeros.
- Alphabetic ranges are also supported.

### Localhost

Use the [localhost()](api.md#localhost) function for local execution:

```php
localhost(); // Alias and hostname are "localhost".
localhost('ci'); // Alias is "ci", hostname is "localhost".
```

Now [run()](api.md#run) will execute on command locally. Alternatively, you can use [runLocally()](api.md#runlocally)
function.

### YAML Inventory

Separate host definitions into an external file using the [import()](api.md#import) function:

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

---

With these tools and configurations, you can manage and deploy to hosts effectively, whether it's a single server or a
complex multi-host setup. Happy deploying!
