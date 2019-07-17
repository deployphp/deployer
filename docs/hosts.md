# Hosts

Defining a host in Deployer is necessary to deploy your application. It can be a remote machine, a local machine or Amazon EC2 instances.
Each host contains a hostname, a stage, one or more roles and configuration parameters. 

You can define hosts with the `host` function in `deploy.php` file. Here is an example of a host definition:

~~~php
host('domain.com')
    ->stage('production')
    ->roles('app')
    ->set('deploy_path', '~/app');
~~~

Host *domain.com* has stage `production`, one role `app` and a configuration parameter `deploy_path` = `~/app`.

Hosts can also be described by using yaml syntax. Write this in a `hosts.yml` file:

~~~yaml
domain.com:
  stage: production
  roles: app
  deploy_path: ~/app
~~~

Then to `deploy.php`:

~~~php
inventory('hosts.yml');
~~~

Make sure that your `~/.ssh/config` file contains information about your domains and how to connect.
Or you can specify that information in the `deploy.php` file itself.

~~~php
host('domain.com')
    ->user('name')
    ->port(22)
    ->configFile('~/.ssh/config')
    ->identityFile('~/.ssh/id_rsa')
    ->forwardAgent(true)
    ->multiplexing(true)
    ->addSshOption('UserKnownHostsFile', '/dev/null')
    ->addSshOption('StrictHostKeyChecking', 'no');
~~~

> **Best practice** is to leave connecting information for hosts in the `~/.ssh/config` file.
> That way you allow different users to connect in different ways.

### Overriding config per host

For example, if you have some global configuration you can override it per host:

~~~php
set('branch', 'master');

host('prod')
    ...
    ->set('branch', 'production');
~~~

Now onthe  _prod_ host the branch is set to `production`, on others to `master`.

### Gathering host info

Inside any task, you can get host config with the `get` function, and the host object with the `host` function.

~~~php
task('...', function () {
    $deployPath = get('deploy_path');
    
    $host = host('domain.com');
    $port = $host->getPort();
});
~~~

### Multiple hosts

You can pass multiple hosts to the `host` function:

~~~php
host('110.164.16.59', '110.164.16.34', '110.164.16.50', ...)
    ->stage('production')
    ...
~~~

If your inventory `hosts.yml` file contains multiple, you can change the config for all of them in the same way.

~~~php
inventory('hosts.yml')
    ->roles('app')
    ...
~~~

### Host ranges

If you have a lot of hosts following similar patterns, you can describe them like this rather than listing each hostname:

~~~php
host('www[01:50].domain.com');
~~~

For numeric patterns, leading zeros can be included or removed, as desired. Ranges are inclusive. 

You can also define alphabetic ranges:

~~~php
host('db[a:f].domain.com');
~~~

### Localhost

If you need to build your release before deploying on a remote machine, or deploy to localhost instead of remote,
you need to define localhost:

~~~php
localhost()
    ->stage('production')
    ->roles('test', 'build')
    ...
~~~

### Host aliases

If you want to deploy an app to one host, but for example in different directories, you can describe two host aliases:

~~~php
host('domain.com/green', 'domain.com/blue')
    ->set('deploy_path', '~/{{hostname}}')
    ...
~~~

For Deployer, those hosts are different ones, and after deploying to both hosts you will see this directory structure:

~~~
~
└── domain.com
    ├── green
    │   └── ...
    └── blue
        └── ...
~~~

### One host for a few stages

Often you have only one server for prod and beta stages. You can easily configure them:

~~~php
host('production')
    ->hostname('domain.com')
    ->set('deploy_path', '~/domain.com');
    
host('beta')
    ->hostname('domain.com')
    ->set('deploy_path', '~/beta.domain.com');    
~~~

Now you can deploy with these commands:

~~~sh
dep deploy production
dep deploy beta
~~~

### Inventory file

Include hosts defined in inventory files `hosts.yml` by `inventory` function:

~~~php
inventory('hosts.yml');
~~~

Here an example of an inventory file `hosts.yml` with the full set of configuration settings

~~~yaml
domain.com:
  hostname: domain.com
  user: name
  port: 22
  configFile: ~/.ssh/config
  identityFile: ~/.ssh/id_rsa
  forwardAgent: true
  multiplexing: true
  sshOptions:
    UserKnownHostsFile: /dev/null
    StrictHostKeyChecking: no
  stage: production
  roles:
    - app
    - db
  deploy_path: ~/app
  extra_param: "foo {{hostname}}"
~~~

> **Note** that, as with the `host` function in the *deploy.php* file, it's better to omit information such as 
> *user*, *port*, *identityFile*, *forwardAgent* and use it from the `~/.ssh/config` file instead.

If your inventory file contains many similar host definitions, you can use YAML extend syntax:

~~~yaml
.base: &base
  roles: app
  deploy_path: ~/app
  ...

www1.domain.com:
  <<: *base
  stage: production
  
beta1.domain.com:
  <<: *base
  stage: beta
    
...
~~~

Hosts that start with `.` (*dot*) are called hidden and are not visible outside that file.
 
To define localhost in inventory files add a `local` key:

~~~yaml
localhost:
  local: true
  roles: build
  ...
~~~

### Become

Deployer allows you to ‘become’ another user, different from the user that logged into the machine (remote user).

~~~php
host('domain.com')
    ->become('deployer')
    ...
~~~

Deployer uses `sudo` privilege escalation method by default.

> **Note** that become doesn't work with `tty` run option.

Next: [deployment flow](flow.md).
