
## Some Solution

```The stream or file "releases/36/storage/logs/laravel.log" could not be opened in append mode: failed to open stream: Permission denied.```

This can be solved by Setting ```writable_chmod_mode``` to ```775``` fixes this issue and allows ```www-data``` group to write to ```logs/laravel.log```.


```deploy.yaml```

```config:   writable_mode: chmod ```

This allows artisan commands such as artisan:storage:link to run successfully without relying on additional task. Considering that artisan:storage:link also runs the same command.

Also writable_chmod_mode defaults to 0755 which is working for artisan commands but if we try and visit the website, we will again be welcomed by another permission error similar to the one I have when trying to run artisan:storage:link with writable_mode set to chown: