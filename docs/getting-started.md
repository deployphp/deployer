# Getting Started

What are we going to do in this tutorial:
- Provision a new server.
- Configure a deployment.
- Automate deployment via GitHub Actions.

Tutorial duration: **10 min**

First, [install the Deployer](installation.md).

## Initialize Deployer

Let's say you have an app called **myapp** and we want to deploy it. The 
**myapp** repository is **github.com/myorg/myapp** and we want to deploy it to
**example.com**.

Let's `cd` into our app repo and run following command:

```bash
dep init
```

Deployer will ask you a few question and after finishing you will have a 
`deploy.php` or `deploy.yaml` file.

## Provision a server

:::note
If you already have configured webserver you may skip to 
[deployment](#configure-a-deployment).
:::

Now let's create a new VPS on Linode, DigitalOcean, Vultr, AWS, GCP, etc. 
Make sure what image is `Ubuntu 20.04 LTS` as this version is supported via 
Deployer [provision](recipe/provision.md) recipe.

:::tip
Configure Reverse DNS or RDNS on your server, this allow you to ssh into server 
using domain name instead of IP address. 
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

Provision will take ~5min. 

After we have configured webserver let's deploy our 
app.

## Configure a deployment

Let's deploy our application:
```bash
dep deploy
```

Now let's ssh to host and edit `.env`.

```bash
dep ssh
```


## Deploy on push
