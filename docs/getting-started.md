# Getting Started

What are we going to do in this tutorial:
- Provision a new server.
- Configure a deployment.
- Automate deployment via GitHub Actions.

Tutorial duration: **10 min**

First, [install the Deployer](installation.md).

Let's `cd` into our app repo and run following command:

```bash
dep init
```

Deployer will ask you a few question and after finishing you will have a 
`deploy.php` or `deploy.yaml` file.

All framework recipes based on [common](recipe/common.md) recipe. 

## Provision a server

:::note
If you already have configured webserver you may skip to 
[deployment](#configure-a-deployment).
:::

Now let's create a new VPS on Linode, DigitalOcean, Vultr, AWS, GCP, etc. 
Make sure what image is `Ubuntu 20.04 LTS` as this version is supported via 
Deployer [provision](recipe/provision.md) recipe.

:::tip
Configure Reverse DNS or RDNS on your server, this will allow you to ssh into 
server using domain name instead of IP address. 
:::

After VPS created, let's try to ssh as root:
```bash
ssh root@example.com
```

Now let's provision our server.

```bash
dep provision -o remote_user=root
```

:::note
We added `-o remote_user=root` to make Deployer use root to connect to host for
provisioning.
:::

Deployer will ask you a few questions during provisioning like PHP version, 
database type, etc.

Provision will take around **~5min** and install everything we need to run a 
site. Also, it will configure `deployer` user what we need to use to ssh to our 
host and a new website was configured at [deploy_path](recipe/common.md#deploy_path).

After we have configured webserver let's deploy our 
app.

## Configure a deployment

Let's deploy our application:
```bash
dep deploy
```

Now we can ssh to host and edit, for example, `.env`.

```bash
dep ssh
```

Also let's add a build step on our host:
```phpr
task('build', function () {
    cd('{{release_path}}');
    run('npm install');
    run('npm run prod');
});

after('deploy:update_code', 'build');
```

Also, Deployer has a useful task for examine what is currently deployed.
```bash
$ dep releases
task releases
+---------------------+--------- deployer.org -------+--------+-----------+
| Date (UTC)          | Release     | Author         | Target | Commit    |
+---------------------+-------------+----------------+--------+-----------+
| 2021-11-05 14:00:22 | 1 (current) | Anton Medvedev | HEAD   | 943ded2be |
+---------------------+-------------+----------------+--------+-----------+
```

:::tip
During development [dep push](recipe/deploy/push.md) task maybe useful.
:::

## Deploy on push

Not let's use [GitHub Action for Deployer](https://github.com/deployphp/action).

Create `.github/workflows/deploy.yml` file with following content:

```yaml
name: deploy

on:
  push:
    branches: [ master ]

concurrency: production_environment

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
  
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.0' 

    - name: Deploy
      uses: deployphp/action@v1
      with:
        private-key: ${{ secrets.PRIVATE_KEY }}
        dep: deploy
```

:::warning
The `concurrency: production_environment` is important as it prevents concurrent 
deploys.
:::
