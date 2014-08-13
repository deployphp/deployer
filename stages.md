---
layout: main
title: Tasks
---

# Stages

You can define stages with `stage` function. Here is example of stage definition:

~~~ php
// stage(string name, array serverlist, array options = array(), bool default = true)
stage('development', array('development-server'), array('branch'=>'develop'), true);
stage('production', array('production-primary', 'production-secondary'), array('branch'=>'master'));
~~~

<h4><a name="default-stage">Default stage</a></h4>

You can defined the default stage with `multistage` function. Here is example of stage definition:

~~~ php
multistage('develop');
~~~

### Options

Besides passing the option through the helper method, it is also possible to add them afterwards.

~~~ php
stage('production', array('production-server'))->options(array('branch'=>'master'));
~~~

It is also possible to set a specific option.

~~~ php
stage('production', array('production-server'))->set('branch','master');
~~~

The options will overwrite the ones set in your deploy.php and just like other options you can retrieve them by calling `get`.

&larr; [Servers](servers.html) &divide; [Verbosity](verbosity.html) &rarr;