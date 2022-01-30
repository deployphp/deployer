## Upgrade from 5.x to 6.x

1. Changed branch option priority

    If you have host definition with `branch(...)` parameter, adding `--branch` option will not override it any more.
    If no `branch(...)` parameter persists, branch will be fetched from current local git branch. 
    
    ```php
    host('prod')
        ->set('branch', 'production')
    ```
    
    In order to return to old behavior add checking of `--branch` option.
    
    ```php
    host('prod')
        ->set('branch', function () {
            return input()->getOption('branch') ?: 'production';
        })
    ```    
    
2. Add `deploy:info` task to the beginning to `deploy` task.
    
3. `run` returns string instead of `Deployer\Type\Result`
   
    Now `run` and `runLocally` returns `string` instead of `Deployer\Type\Result`. 
    Replace method calls as:
    
    * `run('command')->toString()` → `run('command')`
    * `run('if command; then echo "true"; fi;')->toBool()` → `test('command')`

4. `env_vars` renamed to `env`

    * `set('env_vars', 'FOO=bar');` → `set('env', ['FOO' => 'bar']);`

    If your are using Symfony recipe, then you need to change `env` setting:
    
    * `set('env', 'prod');` → `set('symfony_env', 'prod');`
