<?php

namespace RibeiroBreno\Promises\Tests;

use PHPUnit\Framework\TestCase;
use RibeiroBreno\Promises\Promise;
use RibeiroBreno\Promises\Tests\Util\InspectableCallback;

class PromiseBasicTest extends TestCase
{

    /**
     * @covers \RibeiroBreno\Promises\Promise::resolve
     */
    public function testResolvedPromise()
    {
        $result = Promise::resolve('expected value');

        self::assertInstanceOf(Promise::class, $result);

        $thenCB = InspectableCallback::noReturn();
        $catchCB = InspectableCallback::noReturn();

        $result->then($thenCB, $catchCB);
        $result->catch($catchCB);

        self::assertEquals(['expected value'], $thenCB->getLastArgs());
        self::assertEmpty($catchCB->getLastArgs());

        self::assertEquals(1, $thenCB->getTotalCalls(), "Then call count incorrect!");
        self::assertEquals(0, $catchCB->getTotalCalls(), "Catch call count incorrect!");

        $finallyCB = InspectableCallback::noReturn();
        $result->finally($finallyCB);

        self::assertEmpty($finallyCB->getLastArgs());
        self::assertEquals(1, $finallyCB->getTotalCalls(), "Finally call count incorrect!");
    }

    /**
     * @covers \RibeiroBreno\Promises\Promise::reject
     */
    public function testRejectedPromise()
    {
        $result = Promise::reject('expected reason');

        self::assertInstanceOf(Promise::class, $result);

        $thenCB = InspectableCallback::noReturn();
        $catchCB = InspectableCallback::noReturn();

        $result->then($thenCB, $catchCB);
        $result->catch($catchCB);

        self::assertEmpty($thenCB->getLastArgs());
        self::assertEquals(['expected reason'], $catchCB->getLastArgs());

        self::assertEquals(0, $thenCB->getTotalCalls(), "Then call count incorrect!");
        self::assertEquals(2, $catchCB->getTotalCalls(), "Catch call count incorrect!");

        $finallyCB = InspectableCallback::noReturn();
        $result->finally($finallyCB);

        self::assertEmpty($finallyCB->getLastArgs());
        self::assertEquals(1, $finallyCB->getTotalCalls(), "Finally call count incorrect!");
    }

    public function testResolvedPromiseWithDelay()
    {
        $executor = new InspectableCallback(function ($resolve, $reject) {
            self::assertIsCallable($resolve);
            self::assertIsCallable($reject);
        });

        $promise = new Promise($executor);

        self::assertNull($executor->getCaught());

        $executorArgs = $executor->getLastArgs();
        self::assertCount(2, $executorArgs);

        $resolveHandler = $executorArgs[0];
        self::assertIsCallable($resolveHandler);
        self::assertEquals(1, $executor->getTotalCalls(), "Executor should be running!");

        $thenCB = InspectableCallback::noReturn();
        $catchCB = InspectableCallback::noReturn();
        $newPromise = $promise->then($thenCB, $catchCB);

        self::assertInstanceOf(Promise::class, $newPromise);

        self::assertNotSame($promise, $newPromise);

        $finallyCB = InspectableCallback::noReturn();
        $newPromise = $promise->finally($finallyCB);

        self::assertInstanceOf(Promise::class, $newPromise);

        self::assertNotSame($promise, $newPromise);

        self::assertEmpty($thenCB->getLastArgs());
        self::assertEmpty($catchCB->getLastArgs());

        self::assertEquals(0, $thenCB->getTotalCalls(), "Then called before resolution.");
        self::assertEquals(0, $catchCB->getTotalCalls(), "Catch called before resolution.");
        self::assertEquals(0, $finallyCB->getTotalCalls(), "Finally called before actual resolution.");
        self::assertEquals(1, $executor->getTotalCalls(), "Executor should run only once!");

        call_user_func($resolveHandler, 'resolving');

        self::assertSame(['resolving'], $thenCB->getLastArgs());
        self::assertEmpty($catchCB->getLastArgs());

        self::assertEquals(1, $thenCB->getTotalCalls(), "Then call count incorrect!");
        self::assertEquals(0, $catchCB->getTotalCalls(), "Catch call count incorrect!");
        self::assertEquals(1, $finallyCB->getTotalCalls(), "Finally call count incorrect!");
        self::assertEquals(1, $executor->getTotalCalls(), "Executor should run only once!");

        self::assertNull($executor->getCaught());
    }

    public function testRejectedPromiseWithDelay()
    {
        $executor = new InspectableCallback(function ($resolve, $reject) {
            self::assertIsCallable($resolve);
            self::assertIsCallable($reject);
        });

        $promise = new Promise($executor);

        self::assertNull($executor->getCaught());

        $executorArgs = $executor->getLastArgs();
        self::assertCount(2, $executorArgs);

        $rejectHandler = $executorArgs[1];
        self::assertIsCallable($rejectHandler);
        self::assertEquals(1, $executor->getTotalCalls(), "Executor should be running!");

        $thenCB = InspectableCallback::noReturn();
        $catchCB = InspectableCallback::noReturn();
        $newPromise = $promise->then($thenCB, $catchCB);

        self::assertInstanceOf(Promise::class, $newPromise);

        self::assertNotSame($promise, $newPromise);

        $finallyCB = InspectableCallback::noReturn();
        $newPromise = $promise->finally($finallyCB);

        self::assertInstanceOf(Promise::class, $newPromise);

        self::assertNotSame($promise, $newPromise);

        self::assertEmpty($thenCB->getLastArgs());
        self::assertEmpty($catchCB->getLastArgs());

        self::assertEquals(0, $thenCB->getTotalCalls(), "Then called before resolution.");
        self::assertEquals(0, $catchCB->getTotalCalls(), "Catch called before resolution.");
        self::assertEquals(0, $finallyCB->getTotalCalls(), "Finally called before actual resolution.");
        self::assertEquals(1, $executor->getTotalCalls(), "Executor should run only once!");

        call_user_func($rejectHandler, 'rejecting');

        self::assertEmpty($thenCB->getLastArgs());
        self::assertSame(['rejecting'], $catchCB->getLastArgs());

        self::assertEquals(0, $thenCB->getTotalCalls(), "Then call count incorrect!");
        self::assertEquals(1, $catchCB->getTotalCalls(), "Catch call count incorrect!");
        self::assertEquals(1, $finallyCB->getTotalCalls(), "Finally call count incorrect!");
        self::assertEquals(1, $executor->getTotalCalls(), "Executor should run only once!");

        self::assertNull($executor->getCaught());
    }
}
