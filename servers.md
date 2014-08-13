---
layout: main
title: Servers
---

# Servers

Deployer uses ssh2 pecl extension, but if you do not install it on you machine - do not worry,
Deployer will use [PHPSecLib](https://github.com/phpseclib/phpseclib).

You can define servers with `server` function. Here is example of server definition:

~~~ php
server('main', 'site.com')
    ->path('/home/user/site.com')
    ->user('user')
    ->pubKey();
~~~

This function gets 3 parameters `server(server_name, host, port)` and return `Deployer\Server\Configuration` object which contains server configuration.

You need to specify base working path on that server where your project will be deployed with `path()` method.

And specify how to connect to server's SSH. There are a few ways:

<h4><a name="with-username-and-password">With username and password</a></h4>

~~~ php
server(...)
  ->user('name', 'password')
~~~

You can skip password and it will be asked on deploy.

### With public key

~~~ php
server(...)
    ->user('name')
    ->pubKey();
~~~

If you keys created with password or located outside of `.ssh` directory, you can specify it:

~~~ php
server(...)
    ...
    ->pubKey('~/.ssh/id_rsa.pub', '~/.ssh/id_rsa', 'pass phrase');
~~~

Symbol `~` will be replaced with your home directory. If you set pass phrase as `null`,
it will be asked on deploy.

Also you can specify everything with next methods:

~~~ php
server(...)
    ...
    ->setPublicKey(...)
    ->setPrivateKey(...)
    ->setPassPhrase(...);
~~~

### With config file

Deployer can use your SSH config file.

~~~ php
server(...)
    ->user('name')
    ->configFile('/path/to/file');
~~~

This can be used only with installed ssh2 pecl extension.


### With pem file

Auth with pem file now supported only with PhpSecLib.

~~~ php
// Switch to PhpSecLib
set('use_ssh2', false);

server('ec2', 'host.aws.amazon.com')
    ->user('ec2-user')
    ->pemFile('~/.ssh/keys.pem');
~~~

### Upload and download

You can upload file or directory with `upload(local, remote)` function.

And download file with `download(local, remote)` function.


&larr; [Tasks](tasks.html) &divide; [Stages](stages.html) &rarr;