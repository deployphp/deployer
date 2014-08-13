---
layout: main
title: Environment
---

# Environment

To get current environment it task call `env()` function.

~~~ php
task('my_task', function () {
    env()->get(...);
});
~~~

To set environment parameter:

~~~ php
env()->set('key', 'value');
~~~

To get environment parameter:

~~~ php
env()->get('key');
~~~

To get release path:

~~~ php
env()->getReleasePath();
~~~

To get server configuration:

~~~ php
config();

// Is same as

env()->getConfig();
~~~

To set <mark>global</mark> Deployer parameters use `set` and `get`:

~~~ php
set('key', 'value');

get('key');
~~~


&larr; [Verbosity](verbosity.html) &divide; [Functions](functions.html) &rarr;