# Événement

Événement is a very simple event dispatching library for PHP.

It has the same design goals as [Silex](http://silex-project.org) and
[Pimple](http://pimple-project.org), to empower the user while staying concise
and simple.

It is very strongly inspired by the EventEmitter API found in
[node.js](http://nodejs.org).

[![Build Status](https://secure.travis-ci.org/igorw/evenement.png?branch=master)](http://travis-ci.org/igorw/evenement)

## Fetch

The recommended way to install Événement is [through composer](http://getcomposer.org).

Just create a composer.json file for your project:

```JSON
{
    "require": {
        "evenement/evenement": "^3.0 || ^2.0"
    }
}
```

**Note:** The `3.x` version of Événement requires PHP 7 and the `2.x` version requires PHP 5.4. If you are
using PHP 5.3, please use the `1.x` version:

```JSON
{
    "require": {
        "evenement/evenement": "^1.0"
    }
}
```

And run these two commands to install it:

    $ curl -s http://getcomposer.org/installer | php
    $ php composer.phar install

Now you can add the autoloader, and you will have access to the library:

```php
<?php
require 'vendor/autoload.php';
```

## Usage

### Creating an Emitter

```php
<?php
$emitter = new Evenement\EventEmitter();
```

### Adding Listeners

```php
<?php
$emitter->on('user.created', function (User $user) use ($logger) {
    $logger->log(sprintf("User '%s' was created.", $user->getLogin()));
});
```

### Emitting Events

```php
<?php
$emitter->emit('user.created', [$user]);
```

Tests
-----

    $ ./vendor/bin/phpunit

License
-------
MIT, see LICENSE.
