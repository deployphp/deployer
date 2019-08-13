# Inventory Examples

You can choose any inventory management you want or use one of next examples.

### One or two hosts

In most scenarios your project will have one or two hosts: one for production and one for staging.
So there is no need to separate inventory file, you can write everything in single _deploy.php_ file.

For single host you don't need anything. Deployer will deploy to all defined hosts if no _stage_ parameter specified.

```php
set('deploy_path', '~/project');

host('project.com');
```

If you have one host for production and another for staging the next example is sufficient.

> Right behavior for `dep deploy` command is to _deploy staging_, and to deploy prod is `dep deploy production`.

```php
set('application', 'project');
set('deploy_path', '~/{{application}}');
set('default_stage', 'staging');

host('project.com')
    ->stage('production');
    
host('staging.project.com')
    ->stage('staging');
```

> **Best practice** is to leave connecting information for hosts in the `~/.ssh/config` file.
> That way you allow different users to connect in different ways.

### Separate inventory files

TODO
