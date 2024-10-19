# Introduction

Événement is is French and means "event". The événement library aims to
provide a simple way of subscribing to events and notifying those subscribers
whenever an event occurs.

The API that it exposes is almost a direct port of the EventEmitter API found
in node.js. It also includes an "EventEmitter". There are some minor
differences however.

The EventEmitter is an implementation of the publish-subscribe pattern, which
is a generalized version of the observer pattern. The observer pattern
specifies an observable subject, which observers can register themselves to.
Once something interesting happens, the subject notifies its observers.

Pub/sub takes the same idea but encapsulates the observation logic inside a
separate object which manages all of its subscribers or listeners. Subscribers
are bound to an event name, and will only receive notifications of the events
they subscribed to.

**TLDR: What does evenement do, in short? It provides a mapping from event
names to a list of listener functions and triggers each listener for a given
event when it is emitted.**

Why do we do this, you ask? To achieve decoupling.

It allows you to design a system where the core will emit events, and modules
are able to subscribe to these events. And respond to them.
