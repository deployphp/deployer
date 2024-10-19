<?php declare(strict_types=1);

/*
 * This file is part of Evenement.
 *
 * (c) Igor Wiedler <igor@wiedler.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Evenement\Tests;

use Evenement\EventEmitter;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class EventEmitterTest extends TestCase
{
    private $emitter;

    public function setUp()
    {
        $this->emitter = new EventEmitter();
    }

    public function testAddListenerWithLambda()
    {
        $this->emitter->on('foo', function () {});
    }

    public function testAddListenerWithMethod()
    {
        $listener = new Listener();
        $this->emitter->on('foo', [$listener, 'onFoo']);
    }

    public function testAddListenerWithStaticMethod()
    {
        $this->emitter->on('bar', ['Evenement\Tests\Listener', 'onBar']);
    }

    public function testAddListenerWithInvalidListener()
    {
        try {
            $this->emitter->on('foo', 'not a callable');
            $this->fail();
        } catch (\Exception $e) {
        } catch (\TypeError $e) {
        }
    }

    public function testOnce()
    {
        $listenerCalled = 0;

        $this->emitter->once('foo', function () use (&$listenerCalled) {
            $listenerCalled++;
        });

        $this->assertSame(0, $listenerCalled);

        $this->emitter->emit('foo');

        $this->assertSame(1, $listenerCalled);

        $this->emitter->emit('foo');

        $this->assertSame(1, $listenerCalled);
    }

    public function testOnceWithArguments()
    {
        $capturedArgs = [];

        $this->emitter->once('foo', function ($a, $b) use (&$capturedArgs) {
            $capturedArgs = array($a, $b);
        });

        $this->emitter->emit('foo', array('a', 'b'));

        $this->assertSame(array('a', 'b'), $capturedArgs);
    }

    public function testEmitWithoutArguments()
    {
        $listenerCalled = false;

        $this->emitter->on('foo', function () use (&$listenerCalled) {
            $listenerCalled = true;
        });

        $this->assertSame(false, $listenerCalled);
        $this->emitter->emit('foo');
        $this->assertSame(true, $listenerCalled);
    }

    public function testEmitWithOneArgument()
    {
        $test = $this;

        $listenerCalled = false;

        $this->emitter->on('foo', function ($value) use (&$listenerCalled, $test) {
            $listenerCalled = true;

            $test->assertSame('bar', $value);
        });

        $this->assertSame(false, $listenerCalled);
        $this->emitter->emit('foo', ['bar']);
        $this->assertSame(true, $listenerCalled);
    }

    public function testEmitWithTwoArguments()
    {
        $test = $this;

        $listenerCalled = false;

        $this->emitter->on('foo', function ($arg1, $arg2) use (&$listenerCalled, $test) {
            $listenerCalled = true;

            $test->assertSame('bar', $arg1);
            $test->assertSame('baz', $arg2);
        });

        $this->assertSame(false, $listenerCalled);
        $this->emitter->emit('foo', ['bar', 'baz']);
        $this->assertSame(true, $listenerCalled);
    }

    public function testEmitWithNoListeners()
    {
        $this->emitter->emit('foo');
        $this->emitter->emit('foo', ['bar']);
        $this->emitter->emit('foo', ['bar', 'baz']);
    }

    public function testEmitWithTwoListeners()
    {
        $listenersCalled = 0;

        $this->emitter->on('foo', function () use (&$listenersCalled) {
            $listenersCalled++;
        });

        $this->emitter->on('foo', function () use (&$listenersCalled) {
            $listenersCalled++;
        });

        $this->assertSame(0, $listenersCalled);
        $this->emitter->emit('foo');
        $this->assertSame(2, $listenersCalled);
    }

    public function testRemoveListenerMatching()
    {
        $listenersCalled = 0;

        $listener = function () use (&$listenersCalled) {
            $listenersCalled++;
        };

        $this->emitter->on('foo', $listener);
        $this->emitter->removeListener('foo', $listener);

        $this->assertSame(0, $listenersCalled);
        $this->emitter->emit('foo');
        $this->assertSame(0, $listenersCalled);
    }

    public function testRemoveListenerNotMatching()
    {
        $listenersCalled = 0;

        $listener = function () use (&$listenersCalled) {
            $listenersCalled++;
        };

        $this->emitter->on('foo', $listener);
        $this->emitter->removeListener('bar', $listener);

        $this->assertSame(0, $listenersCalled);
        $this->emitter->emit('foo');
        $this->assertSame(1, $listenersCalled);
    }

    public function testRemoveAllListenersMatching()
    {
        $listenersCalled = 0;

        $this->emitter->on('foo', function () use (&$listenersCalled) {
            $listenersCalled++;
        });

        $this->emitter->removeAllListeners('foo');

        $this->assertSame(0, $listenersCalled);
        $this->emitter->emit('foo');
        $this->assertSame(0, $listenersCalled);
    }

    public function testRemoveAllListenersNotMatching()
    {
        $listenersCalled = 0;

        $this->emitter->on('foo', function () use (&$listenersCalled) {
            $listenersCalled++;
        });

        $this->emitter->removeAllListeners('bar');

        $this->assertSame(0, $listenersCalled);
        $this->emitter->emit('foo');
        $this->assertSame(1, $listenersCalled);
    }

    public function testRemoveAllListenersWithoutArguments()
    {
        $listenersCalled = 0;

        $this->emitter->on('foo', function () use (&$listenersCalled) {
            $listenersCalled++;
        });

        $this->emitter->on('bar', function () use (&$listenersCalled) {
            $listenersCalled++;
        });

        $this->emitter->removeAllListeners();

        $this->assertSame(0, $listenersCalled);
        $this->emitter->emit('foo');
        $this->emitter->emit('bar');
        $this->assertSame(0, $listenersCalled);
    }

    public function testCallablesClosure()
    {
        $calledWith = null;

        $this->emitter->on('foo', function ($data) use (&$calledWith) {
            $calledWith = $data;
        });

        $this->emitter->emit('foo', ['bar']);

        self::assertSame('bar', $calledWith);
    }

    public function testCallablesClass()
    {
        $listener = new Listener();
        $this->emitter->on('foo', [$listener, 'onFoo']);

        $this->emitter->emit('foo', ['bar']);

        self::assertSame(['bar'], $listener->getData());
    }


    public function testCallablesClassInvoke()
    {
        $listener = new Listener();
        $this->emitter->on('foo', $listener);

        $this->emitter->emit('foo', ['bar']);

        self::assertSame(['bar'], $listener->getMagicData());
    }

    public function testCallablesStaticClass()
    {
        $this->emitter->on('foo', '\Evenement\Tests\Listener::onBar');

        $this->emitter->emit('foo', ['bar']);

        self::assertSame(['bar'], Listener::getStaticData());
    }

    public function testCallablesFunction()
    {
        $this->emitter->on('foo', '\Evenement\Tests\setGlobalTestData');

        $this->emitter->emit('foo', ['bar']);

        self::assertSame('bar', $GLOBALS['evenement-evenement-test-data']);

        unset($GLOBALS['evenement-evenement-test-data']);
    }

    public function testListeners()
    {
        $onA = function () {};
        $onB = function () {};
        $onC = function () {};
        $onceA = function () {};
        $onceB = function () {};
        $onceC = function () {};

        self::assertCount(0, $this->emitter->listeners('event'));
        $this->emitter->on('event', $onA);
        self::assertCount(1, $this->emitter->listeners('event'));
        self::assertSame([$onA], $this->emitter->listeners('event'));
        $this->emitter->once('event', $onceA);
        self::assertCount(2, $this->emitter->listeners('event'));
        self::assertSame([$onA, $onceA], $this->emitter->listeners('event'));
        $this->emitter->once('event', $onceB);
        self::assertCount(3, $this->emitter->listeners('event'));
        self::assertSame([$onA, $onceA, $onceB], $this->emitter->listeners('event'));
        $this->emitter->on('event', $onB);
        self::assertCount(4, $this->emitter->listeners('event'));
        self::assertSame([$onA, $onB, $onceA, $onceB], $this->emitter->listeners('event'));
        $this->emitter->removeListener('event', $onceA);
        self::assertCount(3, $this->emitter->listeners('event'));
        self::assertSame([$onA, $onB, $onceB], $this->emitter->listeners('event'));
        $this->emitter->once('event', $onceC);
        self::assertCount(4, $this->emitter->listeners('event'));
        self::assertSame([$onA, $onB, $onceB, $onceC], $this->emitter->listeners('event'));
        $this->emitter->on('event', $onC);
        self::assertCount(5, $this->emitter->listeners('event'));
        self::assertSame([$onA, $onB, $onC, $onceB, $onceC], $this->emitter->listeners('event'));
        $this->emitter->once('event', $onceA);
        self::assertCount(6, $this->emitter->listeners('event'));
        self::assertSame([$onA, $onB, $onC, $onceB, $onceC, $onceA], $this->emitter->listeners('event'));
        $this->emitter->removeListener('event', $onB);
        self::assertCount(5, $this->emitter->listeners('event'));
        self::assertSame([$onA, $onC, $onceB, $onceC, $onceA], $this->emitter->listeners('event'));
        $this->emitter->emit('event');
        self::assertCount(2, $this->emitter->listeners('event'));
        self::assertSame([$onA, $onC], $this->emitter->listeners('event'));
    }

    public function testOnceCallIsNotRemovedWhenWorkingOverOnceListeners()
    {
        $aCalled = false;
        $aCallable = function () use (&$aCalled) {
            $aCalled = true;
        };
        $bCalled = false;
        $bCallable = function () use (&$bCalled, $aCallable) {
            $bCalled = true;
            $this->emitter->once('event', $aCallable);
        };
        $this->emitter->once('event', $bCallable);

        self::assertFalse($aCalled);
        self::assertFalse($bCalled);
        $this->emitter->emit('event');

        self::assertFalse($aCalled);
        self::assertTrue($bCalled);
        $this->emitter->emit('event');

        self::assertTrue($aCalled);
        self::assertTrue($bCalled);
    }

    public function testEventNameMustBeStringOn()
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('event name must not be null');

        $this->emitter->on(null, function () {});
    }

    public function testEventNameMustBeStringOnce()
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('event name must not be null');

        $this->emitter->once(null, function () {});
    }

    public function testEventNameMustBeStringRemoveListener()
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('event name must not be null');

        $this->emitter->removeListener(null, function () {});
    }

    public function testEventNameMustBeStringEmit()
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('event name must not be null');

        $this->emitter->emit(null);
    }

    public function testListenersGetAll()
    {
        $a = function () {};
        $b = function () {};
        $c = function () {};
        $d = function () {};

        $this->emitter->once('event2', $c);
        $this->emitter->on('event', $a);
        $this->emitter->once('event', $b);
        $this->emitter->on('event', $c);
        $this->emitter->once('event', $d);

        self::assertSame(
            [
                'event' => [
                    $a,
                    $c,
                    $b,
                    $d,
                ],
                'event2' => [
                    $c,
                ],
            ],
            $this->emitter->listeners()
        );
    }

    public function testOnceNestedCallRegression()
    {
        $first = 0;
        $second = 0;

        $this->emitter->once('event', function () use (&$first, &$second) {
            $first++;
            $this->emitter->once('event', function () use (&$second) {
                $second++;
            });
            $this->emitter->emit('event');
        });
        $this->emitter->emit('event');

        self::assertSame(1, $first);
        self::assertSame(1, $second);
    }
}
