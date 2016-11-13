# Upgrade from 3.x to 4.x

1. Namespace for functions

   Add to beginning of *deploy.php* next line:
    
   ```php
   use function Deployer\{server, task, run, set, get, add};
   ```
   
   If you are using PHP version less than 5.6, you can use this:
   
   ```php
   namespace Deployer;
   ```

2. `env()` to `set()`/`get()`
   
   Rename all calls `env($name, $value)` to `set($name, $value)`.
   
   Rename all rvalue `env($name)` to `get($name)`.
    
   Rename all `server(...)->env(...)` to `server(...)->set(...)`.

3. Writable mode
   
   Deployer v4 use `chgrp` instead of acl. 
   If you want to return to previous mode add `set('writable_mode', 'acl');`.
   
   Also sudo turn off by default. To run commands with sudo add `set('writable_use_sudo', true);`.

4. Moved *NonFatalException*
   
   Rename `Deployer\Task\NonFatalException` to `Deployer\Exception\NonFatalException`.

# Upgrade from 2.x to 3.x

1. ### `->path('...')`   

   Replace your server paths configuration:
   
   ```php
   server(...)
     ->path(...);
   ```
   
   to:
   
   ```php 
   server(...)
     ->env('deploy_path', '...');
   ```
