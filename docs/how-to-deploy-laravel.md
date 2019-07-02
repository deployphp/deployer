# How to deploy Laravel 

Apparently you already have some **Laravel application** and some **server** or **shared hosting**. 
Now you need to automate the process of **deployment**. 
Deployer will helps you in this as it ships with some ready to use recipes for **Laravel** based application. 

Let's start with [installation](installation.md) of Deployer. Run next commands in terminal: 

```sh
curl -LO https://deployer.org/deployer.phar
mv deployer.phar /usr/local/bin/dep
chmod +x /usr/local/bin/dep
```

Next, in your projects directory run:

```sh
dep init -t Laravel
```

Command will create `deploy.php` file for *deploying Laravel*. This file called *recipe* and based on built-in recipe *laravel.php*.
It's contains some host configuration and example task. 

First, we need to configure `repository` config of our application:

```php
set('repository', 'git@github.com:user/project.git');
```

Second, configure host:
 
```php
host('domain.org')
    ->set('deploy_path', '/var/www/html');
```

Make sure what `~/.ssh/config` contains `domain.org` and you can connect to host thought ssh.

Another important parameter it is `deploy_path`, where you project will be located on remote host. 

Let's do our first deploy:

```sh
dep deploy
```

If every think goes well, deployer will create next structure on remote host in `deploy_path`:

```text
├── .dep
├── current -> releases/1
├── releases
│   └── 1
└── shared
    ├── .env
    └── storage
```

* `releases` dir contains *deploy* releases of *Laravel* application,
* `shared` dir contains `.env` config and `storage` which will be symlinked to each release,
* `current` is symlink to last release,
* `.dep` dir contains special metadata for deployer (releases log, `deploy.log` file, etc).

Configure you server to serve files from `current`. For example if you are using nginx next:

```config
server {
  listen 80;
  server_name domain.org;

  root /var/www/html/current/public;

  location / {
    try_files $uri /index.php$is_args$args;
  }
}
```

Now you will be able to serve you **laravel project**:

![Laravel App](images/laravel.png)

If you want to automatically migrate database, *Laravel* recipe ships with `artisan:migrate` task. Add this lines to your `deploy.php`:

```php
after('deploy:update_code', 'artisan:migrate');
```

More about configuration and task declarations in our [documentation](getting-started.md).

...
