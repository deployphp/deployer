# Getting Started

In this tutorial we will cover:

- Setting up a new host with provision recipe.
- Configuring a deployment and perfoming our first deploy.

First, [install the Deployer](installation.md):

Now lets cd into the project and run the following command:

```sh
dep init
```

Deployer will ask you a few question and after finishing you will have a
**deploy.php** or **deploy.yaml** file. This is our deployment recipe.
It contains hosts, tasks and requires other recipes. All framework recipes
that come with Deployer are based on the [common](recipe/common.md) recipe.

## Provision

:::note
If you already have a configured webserver you may skip to
[deployment](#deploy).
:::

Let's create a new VPS on Linode, DigitalOcean, Vultr, AWS, GCP, etc.

Make sure the image is **Ubuntu 20.04 LTS** as this version is supported via
Deployer [provision](recipe/provision.md) recipe.

:::tip
Configure Reverse DNS or RDNS on your server. This will allow you to ssh into
server using the domain name instead of the IP address.
:::

Our **deploy.php** recipe contains a host definition with few important params:

- `remote_user` user's name for ssh connection,
- `deploy_path` host's path where we are going to deploy.

Let's set `remote_user` to be `deployer`. Right now our new server probably has
only `root` user. The provision recipe will create and configure `deployer` user
for us.

```php
host('example.org')
    ->set('remote_user', 'deployer')
    ->set('deploy_path', '~/example');
```

To connect to remote host we need to specify identity key or private key.
We can add our identity key directly into host definition, but better to put it
in **~/.ssh/config** file:

```
Host *
  IdentityFile ~/.ssh/id_rsa
```

Now let's provision our server. As our host doesn't have user `deployer`.
We are going to override `remote_user` for provision via `-o remote_user=root`.

```sh
dep provision -o remote_user=root
```

:::tip
If your server doesn't have a `root` user but your remote user can use `sudo` to
become root, then use:

```sh
dep provision -o become=root
```

:::

Deployer will ask you a few questions during provisioning: php version,
database type, etc. Next Deployer will configure our server and create
the `deployer` user. Provision takes around **5 minutes** and will install
everything we need to run a website. A new website will be configured
at [deploy_path](recipe/common.md#deploy_path).

After we have configured the webserver, let's deploy the project.

## Deploy

To deploy the project:

```sh
dep deploy
```

If deploy failed, Deployer will print error message and command what was unsuccessful.
Most likely we need to configure correct database credentials in _.env_ file or similar.

Ssh to the host, for example, for editing _.env_ file:

```sh
dep ssh
```

After everything is configured properly we can resume our deployment from the
place it stopped (But this is not required, we can just start a new deploy):

```
dep deploy --start-from deploy:migrate
```

After our first successful deployment, we can find next structure on our server:

```
~/example                      // The deploy_path.
 |- current -> releases/1      // Symlink to the current release.
 |- releases                   // Dir for all releases.
    |- 1                       // Actual files location.
       |- ...
       |- .env -> shared/.env  // Symlink to shared .env file.
 |- shared                     // Dirs for shared files between releases.
    |- ...
    |- .env                    // Example: shared .env file.
 |- .dep                       // Deployer configuration files.
```

Configure you webserver to serve the `current` directory. For example, for nginx:

```
root /home/deployer/example/current/public;
index index.php;
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

If you're using provision recipe, Deployer will automatically configure Caddy
webserver to serve from the [public_path](/docs/recipe/provision/website.md#public_path).

Now let's add a build step on our host:

```php
task('build', function () {
    cd('{{release_path}}');
    run('npm install');
    run('npm run prod');
});

after('deploy:update_code', 'build');
```

Deployer has a useful task for examining what is currently deployed.

```
$ dep releases
task releases
+---------------------+--------- deployer.org -------+--------+-----------+
| Date (UTC)          | Release     | Author         | Target | Commit    |
+---------------------+-------------+----------------+--------+-----------+
| 2021-11-05 14:00:22 | 1 (current) | Anton Medvedev | HEAD   | 943ded2be |
+---------------------+-------------+----------------+--------+-----------+
```

:::tip
During development, the [dep push](recipe/deploy/push.md) task maybe useful.
:::
