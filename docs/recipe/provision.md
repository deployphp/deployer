<!-- DO NOT EDIT THIS FILE! -->
<!-- Instead edit recipe/provision.php -->
<!-- Then run bin/docgen -->

# Provision Recipe

```php
require 'recipe/provision.php';
```

[Source](/recipe/provision.php)

* Requires
  * [databases](/docs/recipe/provision/databases.md)
  * [nodejs](/docs/recipe/provision/nodejs.md)
  * [php](/docs/recipe/provision/php.md)
  * [user](/docs/recipe/provision/user.md)
  * [website](/docs/recipe/provision/website.md)

## Configuration
### lsb_release
[Source](https://github.com/deployphp/deployer/blob/master/recipe/provision.php#L19)

Name of lsb_release like: focal, bionic, etc.
As only Ubuntu 20.04 LTS is supported for provision should be the `focal`.

```php title="Default value"
return run("lsb_release -s -c");
```


### provision_user
[Source](https://github.com/deployphp/deployer/blob/master/recipe/provision.php#L43)

Default user to use for provisioning.

```php title="Default value"
'root'
```



## Tasks

### provision
[Source](https://github.com/deployphp/deployer/blob/master/recipe/provision.php#L24)

Provision the server.




This task is group task which contains next tasks:
* [provision:check](/docs/recipe/provision.md#provisioncheck)
* [provision:configure](/docs/recipe/provision.md#provisionconfigure)
* [provision:update](/docs/recipe/provision.md#provisionupdate)
* [provision:upgrade](/docs/recipe/provision.md#provisionupgrade)
* [provision:install](/docs/recipe/provision.md#provisioninstall)
* [provision:ssh](/docs/recipe/provision.md#provisionssh)
* [provision:firewall](/docs/recipe/provision.md#provisionfirewall)
* [provision:user](/docs/recipe/provision/user.md#provisionuser)
* [provision:php](/docs/recipe/provision/php.md#provisionphp)
* [provision:node](/docs/recipe/provision/nodejs.md#provisionnode)
* [provision:databases](/docs/recipe/provision/databases.md#provisiondatabases)
* [provision:composer](/docs/recipe/provision/php.md#provisioncomposer)
* [provision:server](/docs/recipe/provision/website.md#provisionserver)
* [provision:website](/docs/recipe/provision/website.md#provisionwebsite)
* [provision:verify](/docs/recipe/provision.md#provisionverify)


### provision:check
[Source](https://github.com/deployphp/deployer/blob/master/recipe/provision.php#L46)

Checks pre-required state.




### provision:configure
[Source](https://github.com/deployphp/deployer/blob/master/recipe/provision.php#L71)

Collects required params.




### provision:update
[Source](https://github.com/deployphp/deployer/blob/master/recipe/provision.php#L123)

Adds repositories and update.




### provision:upgrade
[Source](https://github.com/deployphp/deployer/blob/master/recipe/provision.php#L140)

Upgrades all packages.




### provision:install
[Source](https://github.com/deployphp/deployer/blob/master/recipe/provision.php#L148)

Installs packages.




### provision:ssh
[Source](https://github.com/deployphp/deployer/blob/master/recipe/provision.php#L183)

Configures the ssh.




### provision:firewall
[Source](https://github.com/deployphp/deployer/blob/master/recipe/provision.php#L195)

Setups a firewall.




### provision:verify
[Source](https://github.com/deployphp/deployer/blob/master/recipe/provision.php#L204)

Verifies what provision was successful.




