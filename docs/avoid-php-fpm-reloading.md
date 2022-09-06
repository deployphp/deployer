# Avoid PHP-FPM Reloading

Deployer symlinks _current_ to latest release dir.

```
current -> releases/3/
releases/
    1/
    2/
    3/
```

## The problem

PHP Opcodes get cached. And if `SCRIPT_FILENAME` contains _current_ symlink, on
new deploy nothing updates. Usually, a solution is simple to reload **php-fpm**
after deploy, but such reload can lead to **dropped** or **failed** requests.
The correct fix is to configure your server set `SCRIPT_FILENAME` to a resolved path.
You can check your server configuration by printing `SCRIPT_FILENAME`.

```php
echo $_SERVER['SCRIPT_FILENAME'];
```

If it prints something like `/home/deployer/example.com/current/index.php` with
_current_ in the path, your server configured incorrectly.

## Fix for Nginx

Nginx has special variable `$realpath_root`, use it to set up `SCRIPT_FILENAME`:

```diff
location ~ \.php$ {
  include fastcgi_params;
  fastcgi_pass unix:/var/run/php/php-fpm.sock;
- fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
+ fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
}
```

## Fix for Caddy

:::tip
If you're already using servers provisioned by Deployer, you don't need to fix
anything, as everything is already configured properly.
:::

Use `resolve_root_symlink`:

```
php_fastcgi * unix//run/php/php-fpm.sock {
    resolve_root_symlink
}
```
