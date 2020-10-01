<!-- DO NOT EDIT THIS FILE! -->
<!-- Instead edit contrib/yarn.php -->
<!-- Then run bin/docgen -->

# yarn

[Source](/contrib/yarn.php)


## Installing

Add to your _deploy.php_

```php
require 'contrib/yarn.php';
```

## Configuration

- **bin/yarn** *(optional)*: set Yarn binary, automatically detected otherwise.

## Usage

```php
after('deploy:update_code', 'yarn:install');
```


* Tasks
  * [`yarn:install`](#yarn:install) — Install Yarn packages


## Tasks
### yarn:install
[Source](/contrib/yarn.php#L28)


