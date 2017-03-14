# Upgrade from 4.x to 5.x

1. Servers to Hosts
   
   * Refactor `server($name, $hostname)` to `host($hostname)`
   * Refactor `localServer($name)` to `localhost()`
   * Rename `serverList($file)` to `inventory($file)`
  
2. Configuration options

   * Rename `{{server.name}}` to `{{hostname}}`
   
3. DotArray syntax

   In v5 access to nested arrays in config via dot notation was removed. 
   If you was using it, consider to move to plain config options.
   
   Refactor this:
   
   ```php
   set('a', ['b' => 1]);
   
   // ...
   
   get('a.b');
   ```
   
   To:
   
   ```php
   set('a_b', 1);
   
   // ...
   
   get('a_b');
   ```

# Upgrade from 3.x to 4.x

1. Namespace for functions

   Add to beginning of *deploy.php* next line:

   ```php
   use function Deployer\{server, task, run, set, get, add, before, after};
   ```

   If you are using PHP version less than 5.6, you can use this:

   ```php
   namespace Deployer;
   ```

2. `env()` to `set()`/`get()`

   Rename all calls `env($name, $value)` to `set($name, $value)`.

   Rename all rvalue `env($name)` to `get($name)`.

   Rename all `server(...)->env(...)` to `server(...)->set(...)`.

3. Moved *NonFatalException*

   Rename `Deployer\Task\NonFatalException` to `Deployer\Exception\NonFatalException`.

4. Prior release cleanup

   Due to changes in release management, the new cleanup task will ignore any prior releases deployed with 3.x.  These will need to be manually removed after migrating to and successfully releasing via 4.x.

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
