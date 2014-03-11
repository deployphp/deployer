[![Build Status](https://travis-ci.org/elfet/deployer.png?branch=master)](https://travis-ci.org/elfet/deployer)

Introduction
------------
There are a lot of deployment tools, even in php. But none of them are simple and functional like Deployer.

Here is simple example of deployment script (deploy.php):
```php
require 'deployer.phar';

$key = '~/.ssh/id_rsa';
task('prod_server', function () use ($key) {
    connect('prod.ssh.domain.com', 'user', $key);
});

task('test_server', function () use ($key) {
    connect('test.ssh.domain.com', 'user', $key);
});

task('upload', function () {
    upload(__DIR__, '/home/domain.com');
});

task('clear', function () {
    run('php bin/clear');
});

task('prod', 'Deploy on production.', ['prod_server', 'upload', 'clear']);
task('test', 'Deploy on test server.', ['test_server', 'upload', 'clear']);

start();
```
Now you can run `php deploy.php prod` command to deploy on production server.

[Here](example/symfony.php) you can see Symfony deployment example script.

Requirements
------------
Deployer is only supported on PHP 5.3.3 and up.

Installation
------------
You can download [deployer as phar](http://deployer.in/deployer.phar) archive or you can use composer:
```
"require": {
    "elfet/deployer": "dev-master@dev"
}
```
If you use phar version, simple require it in your script:
```php
require 'deployer.phar';
```
If your use composer version, require your autoload file and call deployer function.
```php
require 'vendor/autoload.php';
deployer();
```

Development
-----------
This project is still in development. I want to invite developers to join the development.

There are a lot of things needs to be implemented:
* Add rsync support if available.
* Add pecl ss2 extension support if available.
* Write better documentation and tests.

Documentation
-------------
```php
task(name, [description], callback)
```
* name - required, you can run tasks from CLI.
* description - optional, describe your task. If `false` this task will be private and does not available from CLI.
* callback - closure or array of tasks.


```php
connect(server, user, key, group = null)
```
Connect to `server`, login as `user` with `key` which is password or RSA key and add to `group`.

```php
rsa(path, [password])
```
Can be used as `key` in `connect` function with `path` to your RSA key (~/.ssh/id_rsa) and `password` of your key.

```php
cd(path)
```
Change remote directory to given `path`.

```php
upload(from, to)
```
Upload local files or directories `from` to remote `to`.

```php
ignore(array)
```
Ignore this files while uploading directories. `array` of string with `*` patterns.

```php
run(command)
```
Run `command` in directory provided by `cd` function.

```php
runLocally(command)
```
Run `command` locally.

```php
writeln(message)
write(message)
```
Write `message` with/without new line.

```php
group(group_name, function () {
    // run, cd, upload and ext commands
})
```
Run commands `run`, `cd`, `upload` only for group connections.

If your do not want include functions, you can use methods:
```php
$tool = deployer(false);

$tool->task('connect', function () use ($tool) {
    $tool->connect('ssh.domain.com', 'user', 'password');
});
```
Every function is alias to `Deployer\Tool` methods.

License
-------
Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
