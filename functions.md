---
layout: main
title: Functions
---

# Functions

Deployer also provide a lot of helpful functions.

~~~ php
run(string $command)
~~~

Runs command on remote server in working path (`server(...)->path('/working/path')`).

~~~ php
cd(string $path)
~~~

Sets current working path for `run` functions. Every task restore working path to base working path.

~~~ php
runLocally(string $command)
~~~

Runs command on local machine.


~~~ php
 write(string $message)
~~~

Write message in console. You can format message with tags `<info>...</info>`, `<comment></comment>`, `<error></error>`.


~~~ php
 writeln(string $message)
~~~

Same as `write` function, but also writes new line.


~~~ php
ask(string $message, mixed $default)
~~~

Ask user for input. You need to specify default value. This default value will be used in quiet mode too.


~~~ php
askConfirmation(string $message[, bool $default = false])
~~~

Ask user for yes or no input.


~~~ php
askHiddenResponse(string $message)
~~~

Ask user for password.

~~~ php
output()
~~~

Current console output.

&larr; [Environment](environment.html) &divide; [Recipes](recipes.html) &rarr;
