# Getting Started

In this tutorial we will cover:
- Setting up a new host with provision recipe.
- Configuring a deployment and perfoming our first deploy.

Tutorial duration: **5 min**

First, [install the Deployer](installation.md):

```sh
curl -LO https://deployer.org/deployer.phar
mv deployer.phar /usr/local/bin/dep
chmod +x /usr/local/bin/dep
```

Now lets cd into the project and run following command:

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

Configure Reverse DNS or RDNS on your server. This will allow you to ssh into 
server using the domain name instead of the IP address.

Our **deploy.php** recipe contains host definition with few important params:
 - `remote_user` user's name for ssh connection,
 - `deploy_path` host's path where we are going to deploy.

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

Now let's provision our server. As our host doesn't have user name `deployer`, but
only `root` user. We going to override `remote_user` for provision via `-o remote_user=root`.

```sh
dep provision -o remote_user=root
```

Deployer will ask you a few questions during provisioning: php version,
database type, etc. You can specify it also in directly in recipe.

Provision recipe going to do:
- Update and upgrade all Ubuntu packages to latest versions,
- Install all needed packages for our website (acl, npm, git, etc),
- Install php with all needed extensions,
- Install and configure the database,
- Install Caddy websertver and configure our website with SSL certificate,
- Configure ssh and firewall,
- Setup **deployer** user.

Provisioning will take around **5 minutes** and will install everything we need to run a 
website. It will also setup a `deployer` user, which we will need to use to ssh to our 
host. A new website will be configured at [deploy_path](recipe/common.md#deploy_path).

After we have configured the webserver, let's deploy the project.

## Deploy

To deploy the project:

```sh
dep deploy
```

If deployment will fail, Deployer will print error message and command what was unsuccessful. 
Most likely we need to confiure correct database credentials in _.env_ file or similar.

Ssh to the host, for example, for editing _.env_ file:

```sh
dep ssh
```

After everything is configured properly we can resume our deployment from the place it stopped (But this is not required, we can just start a new deploy):

```
dep deploy --start-from deploy:migrate
```

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
