# Deploy strategies

### Single server

In most cases you don't need more than one production server.
It's better to build your release files (as cache, js/css bundles) on that machine as well. 
So your builds don't depend on your local configuration and can be deployed from everywhere.
By default Deployer recipes are designed to fulfill these kind of deployments.  

~~~php
desc('Deploy your project');
task('deploy', [
    'deploy:prepare',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:vendors',
    'deploy:symlink',
]);
~~~

### Build server

If you have a lot of servers where are you going to deploy your application, or you are going to use a CI server,
it's better to build your release on one server and then upload files to the application servers.

To do that create a _build_ local task:

~~~php
task('build', function () {
    run('composer install');
    run('npm install');
    run('npm run build');
    // ...
})->local();
~~~

> Note, you can use a simple task definition too
> ~~~php
> task('build', '
>     composer install
>     npm install
>     npm run build    
>     ...        
> ');
> ~~~

After create an _upload_ task:

~~~php
task('upload', function () {
    upload(__DIR__ . "/", '{{release_path}}');
});
~~~


Next, create release and deploy tasks:

~~~php
task('release', [
    'deploy:prepare',
    'deploy:release',
    'upload',
    'deploy:shared',
    'deploy:writable',
    'deploy:symlink',
]);

task('deploy', [
    'build',
    'release',
    'cleanup',
    'success'
]);
~~~

Now you can run the `dep deploy` command.

### Reuse common recipe

If you want to reuse some tasks from the common recipe, make sure that you set the `deploy_path` before invoking tasks.
All common recipe tasks rely on this parameter.

~~~php
task('build', function () {
    set('deploy_path', __DIR__ . '/.build');
    invoke('deploy:prepare');
    invoke('deploy:release');
    invoke('deploy:update_code');
    invoke('deploy:vendors');
    // Add more build steps here
    invoke('deploy:symlink');
})->local();
~~~

> Make sure that you set `deploy_path` before invoking tasks.

After create an upload task:

~~~php
task('upload', function () {
    upload(__DIR__ . "/.build/current/", '{{release_path}}');
});
~~~

This task takes content from the current symlink of `deploy_path` from the build step and then uploads it to the application `release_path` path.

### Prevent unnecessary deployment

If your deployment goal is only to pull down the latest changes from your git repo, you can prevent redundant deployments with the `deploy:check_remote` task. This will compare your remote head with the last deployed and cancel the deployment if they match. This can provide a helpful hint if you've forgotten to push your latest commits.

~~~php
after('deploy:prepare', 'deploy:check_remote');
~~~
