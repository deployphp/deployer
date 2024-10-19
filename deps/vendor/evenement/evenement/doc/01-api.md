# API

The API that événement exposes is defined by the
`Evenement\EventEmitterInterface`. The interface is useful if you want to
define an interface that extends the emitter and implicitly defines certain
events to be emitted, or if you want to type hint an `EventEmitter` to be
passed to a method without coupling to the specific implementation.

## on($event, callable $listener)

Allows you to subscribe to an event.

Example:

```php
$emitter->on('user.created', function (User $user) use ($logger) {
    $logger->log(sprintf("User '%s' was created.", $user->getLogin()));
});
```

Since the listener can be any callable, you could also use an instance method
instead of the anonymous function:

```php
$loggerSubscriber = new LoggerSubscriber($logger);
$emitter->on('user.created', array($loggerSubscriber, 'onUserCreated'));
```

This has the benefit that listener does not even need to know that the emitter
exists.

You can also accept more than one parameter for the listener:

```php
$emitter->on('numbers_added', function ($result, $a, $b) {});
```

## once($event, callable $listener)

Convenience method that adds a listener which is guaranteed to only be called
once.

Example:

```php
$conn->once('connected', function () use ($conn, $data) {
    $conn->send($data);
});
```

## emit($event, array $arguments = [])

Emit an event, which will call all listeners.

Example:

```php
$conn->emit('data', [$data]);
```

The second argument to emit is an array of listener arguments. This is how you
specify more args:

```php
$result = $a + $b;
$emitter->emit('numbers_added', [$result, $a, $b]);
```

## listeners($event)

Allows you to inspect the listeners attached to an event. Particularly useful
to check if there are any listeners at all.

Example:

```php
$e = new \RuntimeException('Everything is broken!');
if (0 === count($emitter->listeners('error'))) {
    throw $e;
}
```

## removeListener($event, callable $listener)

Remove a specific listener for a specific event.

## removeAllListeners($event = null)

Remove all listeners for a specific event or all listeners all together. This
is useful for long-running processes, where you want to remove listeners in
order to allow them to get garbage collected.
