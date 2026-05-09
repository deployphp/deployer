# Hosts

Define hosts with [host()](api.md#host).

### Defining a Host

```php
host('example.org');
```

Hosts hold key-value config. Each host gets two keys for free:

- **`hostname`** — used for the SSH connection.
- **`alias`** — unique identifier in the recipe.

### Reading Host Configurations

Read host config inside a task with [currentHost()](api.md#currenthost):

```php
task('test', function () {
    $hostname = currentHost()->get('hostname');
    $alias = currentHost()->get('alias');
    writeln("The $alias is $hostname");
});
```

Or with `{{...}}` interpolation:

```php
task('test', function () {
    writeln('The {{alias}} is {{hostname}}');
});
```

```sh
$ dep test
[example.org] The example.org is example.org
```

### Overriding Hostname

Override `hostname` to connect to a different address than the alias suggests:

```php
host('example.org')
    ->set('hostname', 'example.cloud.google.com');
```

The alias stays `example.org`; SSH connects to `example.cloud.google.com`.

```sh
$ dep test
[example.org] The example.org is example.cloud.google.com
```

### Configuring Remote User

Set `remote_user` to choose the SSH user:

```php
host('example.org')
    ->set('hostname', 'example.cloud.google.com')
    ->set('remote_user', 'deployer');
```

Connection becomes `ssh deployer@example.cloud.google.com`.

The typed setter methods give better IDE autocompletion:

```php
host('example.org')
    ->setHostname('example.cloud.google.com')
    ->setRemoteUser('deployer')
    ->setBranch('production');
```

---

## Host Labels

Labels are key-value tags used to group hosts:

```php
host('example.org')->setLabels(['stage' => 'prod']);
host('staging.example.org')->setLabels(['stage' => 'staging']);
```

They scale to multi-server fleets:

```php
host('admin.example.org')->setLabels(['stage' => 'prod', 'role' => 'web']);
host('web[1:5].example.org')->setLabels(['stage' => 'prod', 'role' => 'web']);
host('db[1:2].example.org')->setLabels(['stage' => 'prod', 'role' => 'db']);
host('test.example.org')->setLabels(['stage' => 'test', 'role' => 'web']);
host('special.example.org')->setLabels(['role' => 'special']);
```

### Filtering Hosts by Labels

Filter at deploy time with a [selector](selector.md):

```sh
$ dep deploy stage=prod&role=web,role=special
```

- `&` — all conditions must match the same host (AND).
- `,` — match either group (OR).

Set a default selector:

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
| **`branch`**           | Git branch to deploy for this host. Overrides the global `branch` config when set. Use `setBranch()` for a typed setter. |
| **`labels`**           | Key-value pairs for host selection.                                                            |
| **`ssh_arguments`**    | Additional SSH options. E.g., `['-o UserKnownHostsFile=/dev/null']`.                           |
| **`ssh_control_path`** | Control path for SSH multiplexing. Default is `~/.ssh/%C` or `/dev/shm/%C` in CI environments. |

### Best Practices

Keep sensitive SSH parameters out of `deploy.php`. Put them in `~/.ssh/config` instead:

```
Host *
  IdentityFile ~/.ssh/id_rsa
```

---

## Advanced Host Definitions

### Multiple Hosts

Define several hosts with shared config in one call:

```php
host('example.org', 'deployer.org', 'another.org')->setRemoteUser('anton');
```

### Host Ranges

Expand a range into many hosts:

```php
host('www[01:50].example.org'); // www01.example.org … www50.example.org
host('db[a:f].example.org');    // dba.example.org … dbf.example.org
```

Numeric ranges keep leading zeros; alphabetic ranges work too.

### Localhost

Run commands on the local machine with [localhost()](api.md#localhost):

```php
localhost();      // alias and hostname are "localhost"
localhost('ci');  // alias is "ci", hostname is "localhost"
```

[run()](api.md#run) then executes locally. [runLocally()](api.md#runlocally) does the same without needing a
localhost host.

### YAML Inventory

Move host definitions to a separate file and pull them in with [import()](api.md#import):

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
