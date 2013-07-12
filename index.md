---
layout: main
title: Deployment Tool for PHP
---
Introduction
------------
There are a lot of deployment tools, even in php. But none of them are simple and functional like Deployer.

Requirements
------------
Deployer is only supported on PHP 5.3.3 and up.

Installation
------------
You can download deployer as phar archive or you can use composer:
```
"require": {
    "elfet/deployer": "0.*@dev"
}
```

Development
-----------
This project is still in development. I want to invite developers to join the development.

There are a lot of thing need to be implemented:
* Add rsync support if available.
* Add pecl ss2 extension support if available.
* Write better documentation and tests.

Configuration
-------------
Create deploy.php file in your project with this:
```php
require __DIR__ . '/vendor/autoload.php';
new Deployer\Tool();

task('connect', function () {
    connect('ssh.domain.com', 'user', 'password');
});

task('upload', function () {
    upload(__DIR__, '/home/domain.com');
});

task('update', ['connect', 'upload']);
```
And then run next command `php deploy.php update`. That's all!

Documentation
-------------
```
task(name, [description], callback)
```
* name - required, you can run tasks from CLI.
* description - optional, describe your task.
* callback - closure or array of tasks.


```
connect(server, user, key)
```
Connect to `server` and login as `user` with `key` which is password or RSA key.

```
rsa(path, [password])
```
Can be used as `key` in `connect` function with `path` to your RSA key (~/.ssh/id_rsa) and `password` of your key.

```
cd(path)
```
Change remote directory to given `path`.

```
upload(from, to)
```
Upload local files or directories `from` to remote `to`.

```
ignore(array)
```
Ignore this files while uploading directories. `array` of string with `*` patterns.

```
run(command)
```
Run `command` on remote server in directory provided by `cd` function.

```
writeln(message)
write(message)
```
Write `message` with/without new line.

If your do not want include functions, you can use methods:
```php
$tool = new Deployer\Tool(false);

$tool->task('connect', function () use ($tool) {
    $tool->connect('ssh.domain.com', 'user', 'password');
});
```
Every function is alias to `Deployer\Tool` methods.

License
-------
Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php