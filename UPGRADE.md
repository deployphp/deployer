# Upgrade from 3.x to 4.x

1. 


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
