<?php

namespace RibeiroBreno\Promises\Tests;

use PHPUnit\Framework\TestCase;
use RibeiroBreno\Promises\Promise;
use RibeiroBreno\Promises\Tests\Util\InspectableCallback;

class PromiseExceptionTest extends TestCase
{

    public function testConstructor()
    {
        self::expectExceptionMessage('Thrown from executor');

        new Promise(function () {
            throw new \Exception('Thrown from executor');
        });
    }

    public function testRejectedPromise()
    {
        $promise = Promise::reject('reason');

        $thenCB = InspectableCallback::noReturn();
        $thenCB->setThrow(new \Exception('then callback exception'));
        $catchCB = InspectableCallback::identity();
        $catchCB->setThrow(new \Exception('catch callback exception'));
        $catchCB2 = InspectableCallback::noReturn();
        $finallyCB = InspectableCallback::noReturn();

        $promise->then($thenCB, $catchCB)->catch($catchCB2)->finally($finallyCB);

        self::assertSame(0, $thenCB->getTotalCalls());
        self::assertSame(1, $catchCB->getTotalCalls());
        self::assertSame(1, $catchCB2->getTotalCalls());
        self::assertSame(1, $finallyCB->getTotalCalls());
        self::assertEquals([new \Exception('catch callback exception')], $catchCB2->getLastArgs());
    }

    public function testResolvedPromise()
    {
        $promise = Promise::resolve('value');

        $thenCB = InspectableCallback::noReturn();
        $thenCB->setThrow(new \Exception('then callback exception'));
        $catchCB = InspectableCallback::identity();
        $catchCB->setThrow(new \Exception('catch callback exception'));
        $catchCB2 = InspectableCallback::noReturn();
        $finallyCB = InspectableCallback::noReturn();

        $promise->then($thenCB, $catchCB)->catch($catchCB2)->finally($finallyCB);

        self::assertSame(1, $thenCB->getTotalCalls());
        self::assertSame(0, $catchCB->getTotalCalls());
        self::assertSame(1, $catchCB2->getTotalCalls());
        self::assertSame(1, $finallyCB->getTotalCalls());
        self::assertEquals([new \Exception('then callback exception')], $catchCB2->getLastArgs());
    }

    public function testRejectedPromiseWithDelay()
    {
        $executor = InspectableCallback::noReturn();

        $promise = new Promise($executor);
        list(, $rejectHandler) = $executor->getLastArgs();


        $thenCB = InspectableCallback::noReturn();
        $thenCB->setThrow(new \Exception('then callback exception'));
        $catchCB = InspectableCallback::identity();
        $catchCB->setThrow(new \Exception('catch callback exception'));
        $catchCB2 = InspectableCallback::noReturn();
        $finallyCB = InspectableCallback::noReturn();

        $promise->then($thenCB, $catchCB)->catch($catchCB2)->finally($finallyCB);

        self::assertSame(0, $thenCB->getTotalCalls());
        self::assertSame(0, $catchCB->getTotalCalls());
        self::assertSame(0, $catchCB2->getTotalCalls());
        self::assertSame(0, $finallyCB->getTotalCalls());

        call_user_func($rejectHandler, 'reason');

        self::assertSame(0, $thenCB->getTotalCalls());
        self::assertSame(1, $catchCB->getTotalCalls());
        self::assertSame(1, $catchCB2->getTotalCalls());
        self::assertSame(1, $finallyCB->getTotalCalls());
        self::assertEquals([new \Exception('catch callback exception')], $catchCB2->getLastArgs());
    }

    public function testResolvedPromiseWithDelay()
    {
        $executor = InspectableCallback::noReturn();

        $promise = new Promise($executor);
        list($resolveHandler,) = $executor->getLastArgs();

        $thenCB = InspectableCallback::noReturn();
        $thenCB->setThrow(new \Exception('then callback exception'));
        $catchCB = InspectableCallback::identity();
        $catchCB->setThrow(new \Exception('catch callback exception'));
        $catchCB2 = InspectableCallback::noReturn();
        $finallyCB = InspectableCallback::noReturn();

        $promise->then($thenCB, $catchCB)->catch($catchCB2)->finally($finallyCB);

        self::assertSame(0, $thenCB->getTotalCalls());
        self::assertSame(0, $catchCB->getTotalCalls());
        self::assertSame(0, $catchCB2->getTotalCalls());
        self::assertSame(0, $finallyCB->getTotalCalls());

        call_user_func($resolveHandler, 'value');

        self::assertSame(1, $thenCB->getTotalCalls());
        self::assertSame(0, $catchCB->getTotalCalls());
        self::assertSame(1, $catchCB2->getTotalCalls());
        self::assertSame(1, $finallyCB->getTotalCalls());
        self::assertEquals([new \Exception('then callback exception')], $catchCB2->getLastArgs());
    }

    public function testFinally()
    {
        // Instantly resolved.
        $finallyCB = InspectableCallback::noReturn();
        $finallyCB->setThrow(new \Exception('throw 1'));
        $catchCB = InspectableCallback::noReturn();

        Promise::resolve('value')->finally($finallyCB)->catch($catchCB);
        self::assertEquals([new \Exception('throw 1')], $catchCB->getLastArgs());

        // Instantly rejected.
        $catchCB->reset();
        $finallyCB->setThrow(new \Exception('throw 2'));

        Promise::reject('reason')->finally($finallyCB)->catch($catchCB);
        self::assertEquals([new \Exception('throw 2')], $catchCB->getLastArgs());

        // Delayed resolved.
        $catchCB->reset();
        $finallyCB->setThrow(new \Exception('throw 3'));
        $executor = InspectableCallback::noReturn();

        $promise = new Promise($executor);
        $promise->finally($finallyCB)->catch($catchCB);

        list($rejectHandler, ) = $executor->getLastArgs();
        call_user_func($rejectHandler, 'value');

        self::assertEquals([new \Exception('throw 3')], $catchCB->getLastArgs());

        // Delayed rejected.
        $catchCB->reset();
        $finallyCB->setThrow(new \Exception('throw 4'));
        $executor = InspectableCallback::noReturn();

        $promise = new Promise($executor);
        $promise->finally($finallyCB)->catch($catchCB);

        list(, $rejectHandler) = $executor->getLastArgs();
        call_user_func($rejectHandler, 'reason');

        self::assertEquals([new \Exception('throw 4')], $catchCB->getLastArgs());
    }

}
