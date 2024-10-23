# Known Bugs

## Ubuntu 14.04, Coreutils 8.21

There are known bugs with relative symlinks `ln --relative`, which may cause the rollback command to fail.

Add the following line to your _deploy.php_ file:

```php
set('use_relative_symlink', false);
```

## OpenSSH_7.2p2

ControlPersist causes stderr to be left open until the master connection times out.

- https://bugs.debian.org/cgi-bin/bugreport.cgi?bug=714526
- https://bugzilla.mindrot.org/show_bug.cgi?id=1988

## cURL 7.29.0

Certificate verification fails with multiple https urls.

- https://bugzilla.redhat.com/show_bug.cgi?id=1241172

## Rsync (3.1.3)

Artifact upload with `rsync` is interrupted after the first chunk of data upload.

```
The command "rsync -azP -e 'ssh -A -p *** -o UserKnownHostsFile=/dev/null
  -o StrictHostKeyChecking=no' 'artifacts/artifact.tar.gz' 'deploy@ssh.XXX.io:/srv/releases/2009076181'" failed.

Exit Code: 255(Unknown error)

Output:
================
sending incremental file list
artifact.tar.gz
     32,768   0%    0.00kB/s    0:00:00

Error Output:
================
client_loop: send disconnect: Broken pipe

rsync: [sender] write error: Broken pipe (32)
```

In order to resolve (workaround) the issue, you need to add `--bwlimit=4096` to the list of options.

Example:

```php
task('artifact:upload', function () {
    upload(get('artifact_path'), '{{release_path}}', ['options' => ['--bwlimit=4096']]);
});
```

The issue was also described in the [Github Action](https://github.com/deployphp/action/issues/35).
