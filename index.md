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
{% highlight php %}
"require": {
    "elfet/deployer": "0.*@dev"
}
{% endhighlight %}

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
{% highlight php %}
require __DIR__ . '/vendor/autoload.php';
new Deployer\Tool();

task('connect', function () {
    connect('ssh.domain.com', 'user', 'password');
});

task('upload', function () {
    upload(__DIR__, '/home/domain.com');
});

task('update', ['connect', 'upload']);
{% endhighlight %}
And then run next command `php deploy.php update`. That's all!

Documentation
-------------
{% highlight php %}
task(name, [description], callback)
{% endhighlight %}
* name - required, you can run tasks from CLI.
* description - optional, describe your task.
* callback - closure or array of tasks.


{% highlight php %}
connect(server, user, key)
{% endhighlight %}
Connect to `server` and login as `user` with `key` which is password or RSA key.

{% highlight php %}
rsa(path, [password])
{% endhighlight %}
Can be used as `key` in `connect` function with `path` to your RSA key (~/.ssh/id_rsa) and `password` of your key.

{% highlight php %}
cd(path)
{% endhighlight %}
Change remote directory to given `path`.

{% highlight php %}
upload(from, to)
{% endhighlight %}
Upload local files or directories `from` to remote `to`.

{% highlight php %}
ignore(array)
{% endhighlight %}
Ignore this files while uploading directories. `array` of string with `*` patterns.

{% highlight php %}
run(command)
{% endhighlight %}
Run `command` on remote server in directory provided by `cd` function.

{% highlight php %}
writeln(message)
write(message)
{% endhighlight %}
Write `message` with/without new line.

If your do not want include functions, you can use methods:
{% highlight php %}
$tool = new Deployer\Tool(false);

$tool->task('connect', function () use ($tool) {
    $tool->connect('ssh.domain.com', 'user', 'password');
});
{% endhighlight %}
Every function is alias to `Deployer\Tool` methods.

License
-------
Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php