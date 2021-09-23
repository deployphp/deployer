# rsync

[Source](/src/Utility/Rsync.php)

## IMPORTANT

This is the deployer's built-in rsync and must not be confused with `/contrib/rsync.php`. Their configuration options are also very different, read carefully below.

## Configuration options

- **rsync**: Accepts an array with following options (all are optional and defaults are ok):
    - *timeout*: accepts an *int* defining timeout for rsync command to run locally.
    - *options*: accepts an *array* of options to set when calling rsync command. Options **MUST BE** prefixed with `--`.
    - *progress_bar*: displays a progress bar. Defaults to `true`
    - *display_stats*: rsync's `--stats`. Defaults to `false`
    - *flags*: accepts a *string* of flags to set when calling rsync command. Please **avoid** flags that accept params, and use *options* instead. Defaults to `-azP`

### Sample Configuration:

```php
upload('vendor', '{{ release_path }}', ['options' => ['--copy-links', '--delete', '--exclude=.git']]);
download('vendor', '{{ release_path }}', ['options' => ['--copy-links', '--delete', '--exclude=.git']]);
```