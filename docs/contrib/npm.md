<!-- DO NOT EDIT THIS FILE! -->
<!-- Instead edit contrib/npm.php -->
<!-- Then run bin/docgen -->

# npm

[Source](/contrib/npm.php)


## Installing

Add to your _deploy.php_

~~~php
require 'contrib/npm.php';
~~~

## Configuration

- `bin/npm` *(optional)*: set npm binary, automatically detected otherwise.

## Usage

~~~php
after('deploy:update_code', 'npm:install');
~~~

or if you want use `npm ci` command
~~~php
after('deploy:update_code', 'npm:ci');
~~~



* Tasks
  * [`npm:install`](#npm:install) — Install npm packages
  * [`npm:ci`](#npm:ci) — Install npm packages with a clean slate


## Tasks
### npm:install
[Source](/contrib/npm.php#L34)



### npm:ci
[Source](/contrib/npm.php#L50)


