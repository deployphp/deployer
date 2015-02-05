Upgrade from 2.x to 3.x
=======================

### `->path('...')`

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
