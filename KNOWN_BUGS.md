# Known Bugs

## Ubuntu 14.04, Coreutils 8.21

There are known bug with relative symlinks `ln --relative`, which may fail rollback command. 

Add next line to _deploy.php_ file:

~~~php
set('use_relative_symlink', false);
~~~
