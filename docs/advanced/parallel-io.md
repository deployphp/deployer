# Dealing with IO in parallel mode

If you try to make a task which will be asking a user, for example about a branch,
but you still want to use parallel deploy, you may notice that it's now working and the program doesn't wait for user input.

To workaround this problem, you need to create a local task and ask the user about the branch there:

~~~php
task('what_branch', function () {
    $branch = ask('What branch to deploy?');

    on(roles('app'), function ($host) use ($branch) {
        set('branch', $branch);
    });
})->local();
~~~

And call this task before the `deploy` task:

~~~php
before('deploy', 'what_branch');
~~~

Now it should work as expected and the user will be asked for the branch only once.

~~~sh
$ dep deploy -p
➤ Executing task what_branch
What branch to deploy? master
✔ Ok
✔ Executing task deploy:prepare
✔ Executing task deploy:release
...
~~~
