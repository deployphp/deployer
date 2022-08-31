# Known Bugs

## Ubuntu 14.04, Coreutils 8.21

There are known bug with relative symlinks `ln --relative`, which may fail rollback command.

Add next line to _deploy.php_ file:

```php
set('use_relative_symlink', false);
```

## OpenSSH_7.2p2

ControlPersist causes stderr to be left open until the master connection times out.

* https://bugs.debian.org/cgi-bin/bugreport.cgi?bug=714526
* https://bugzilla.mindrot.org/show_bug.cgi?id=1988

## cURL 7.29.0

Certificate verification fails with multiple https urls.

* https://bugzilla.redhat.com/show_bug.cgi?id=1241172
