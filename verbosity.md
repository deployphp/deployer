---
layout: main
title: Verbosity
---

# Verbosity

Deployer has levels of verbosity. To specify it add one of next options to `dep` command.

* `--quiet (-q)` Do not output any message.
* `--verbose (-v|vv|vvv)` Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug.

In task you can check verbosity level with next methods:

~~~ php
task('my_task', function () {
    if (output()->isQuiet()) {
        // ...
    }

    if (output()->isVerbose()) {
        // ...
    }

    if (output()->isVeryVerbose()) {
        // ...
    }

    if (output()->isDebug()) {
        // ...
    }
});
~~~

&larr; [Stages](stages.html) &divide; [Environment](environment.html) &rarr;