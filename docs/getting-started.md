# Getting Started

This tutorial will cover:
- Setting up a new server with provision recipe.
- Configure deployment and do our first deploy.

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

Now let's provision our server.

```sh
dep provision -o remote_user=root
```

We added `-o remote_user=root` to make Deployer use the root user to connect to host 
for provisioning.

Deployer will ask you a few questions during provisioning like what php version and
database type you would like to use.

Provisioning will take around **5 minutes** and will install everything we need to run a 
site. It will also configure a `deployer` user we will need to use to ssh to our 
host. A new website will be configured at [deploy_path](recipe/common.md#deploy_path).

After we have configured the webserver, let's deploy the project.

## Deploy

To deploy the project:
```sh
dep deploy
```

Ssh to host, for example, for editing _.env_ file.

```sh
dep ssh
```

Let's add a build step on our host:
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
