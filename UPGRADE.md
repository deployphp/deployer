# Upgrade from 3.x to 4.x

1. `env()` to `set()`/`get()`
   
   Rename all calls `env($name, $value)` to `set($name, $value)`.
   
   Rename all rvalue `env($name)` to `get($name)`.
    
   Rename all `server(...)->env(...)` to `server(...)->set(...)`.


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
