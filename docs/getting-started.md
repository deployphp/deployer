# Getting Started

This tutorial will guide you through:

- Setting up a new host with the [provision](recipe/provision.md) recipe.
- Configuring a deployment and performing your first deploy.

## Step 1: Install Deployer {#install}

First, [install Deployer](installation.md). Once installed, navigate to your project directory and run:

```sh
dep init
```

Deployer will prompt you with a series of questions. After completing them, you'll have a **deploy.php** or 
**deploy.yaml** file—your deployment recipe. This file defines hosts, tasks, and dependencies on other recipes.
Framework-specific recipes provided by Deployer are based on the [common](recipe/common.md) recipe.

---

## Step 2: Provision a New Server {#provision}

:::note
If you already have a configured web server, skip to [deployment](#deploy).
:::

### Setting Up Your VPS

Create a new VPS with a provider like Linode, DigitalOcean, Vultr, AWS, or GCP. Use an **Ubuntu** image, as it's
supported by Deployer's [provision](recipe/provision.md) recipe.

To utilize Deployer for server provisioning, you must initially configure your server to permit key-based authentication for the root user (which is disabled by default for recent Ubuntu images). Once provisioning is complete, this root access via SSH can be disabled.

:::tip
Set up a DNS record pointing your domain to your server's IP address. This allows you to SSH into the server using your
domain name instead of its IP.
:::

### Configuring `deploy.php`

Your **deploy.php** recipe should define your host with key parameters:

- **`remote_user`**: The SSH username.
- **`deploy_path`**: The file path where your project will be deployed.

Example:

```php
host('example.org')
    ->set('remote_user', 'deployer')
    ->set('deploy_path', '~/example');
```

If your server only has a `root` user, the `provision` recipe will create and configure a `deployer` user for you.

### Adding an Identity Key

To connect to your server, use an identity key or private key. Instead of defining it directly in your host
configuration, add it to your **~/.ssh/config** file:

```
Host *
  IdentityFile ~/.ssh/id_rsa
```

### Provisioning the Server

Run the following command to provision your server:

```sh
dep provision
```

:::tip

- To change the default `root` user, use:
  ```sh
  dep provision -o provision_user=your-user
  ```
- If your remote user can `sudo` to become root, use:
  ```sh
  dep provision -o become=root
  ```

:::

During provisioning, Deployer will ask about PHP versions, database preferences, and more. It takes about **5 minutes**
and installs everything required to run a website. The deployment path is configured
as [deploy_path](recipe/common.md#deploy_path).

---

## Step 3: Deploy Your Project {#deploy}

Deploy your project with:

```sh
dep deploy
```

If the deployment fails, Deployer will display the error and the failed command. You may need to configure your `.env`
file or similar credentials. To edit files directly on the server:

```sh
dep ssh
```

If needed, resume deployment from the last step:

```sh
dep deploy --start-from deploy:migrate
```

---

## Step 4: Post-Deployment Configuration

After the first successful deployment, the server directory structure looks like this:

```
~/example                      // deploy_path
 |- current -> releases/1      // Symlink to current release
 |- releases                   // Directory for all releases
    |- 1                       // Latest release
       |- ...
       |- .env -> shared/.env  // Symlink to shared .env file
 |- shared                     // Shared files between releases
    |- ...
    |- .env                    // Shared .env file
 |- .dep                       // Deployer configuration files
```

### Web Server Setup

Configure your web server to serve from the `current` directory. Example for Nginx:

```nginx
root /home/deployer/example/current/public;
index index.php;
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

For those using the [provision recipe](recipe/provision.md), Deployer will automatically configure the Caddy web server
to serve from the [public_path](recipe/provision/website.md#public_path).

---

## Step 5: Adding a Build Step

To automate build steps, add a task in your **deploy.php**:

```php
task('build', function () {
    cd('{{release_path}}');
    run('npm install');
    run('npm run prod');
});

after('deploy:update_code', 'build');
```

---

## Examining Deployments

Use the `releases` task to view deployment details:

```sh
dep releases
```

Example output:

```
+---------------------+--------- deployer.org -------+--------+-----------+
| Date (UTC)          | Release     | Author         | Target | Commit    |
+---------------------+-------------+----------------+--------+-----------+
| 2021-11-05 14:00:22 | 1 (current) | Anton Medvedev | HEAD   | 943ded2be |
+---------------------+-------------+----------------+--------+-----------+
```

:::tip
During development, the [dep push](recipe/deploy/push.md) task maybe useful
to create a patch of local changes and push them to the host.
:::

--- 

With Deployer, you're now ready to efficiently set up, provision, and manage deployments for your projects!
