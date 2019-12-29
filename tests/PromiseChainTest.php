<?php

namespace RibeiroBreno\Promises\Tests;

use PHPUnit\Framework\TestCase;
use RibeiroBreno\Promises\Promise;
use RibeiroBreno\Promises\Tests\Util\InspectableCallback;

class PromiseChainTest extends TestCase
{

    public function testReturnValue()
    {
        $promise = Promise::resolve('a');

        $thenCB = new InspectableCallback(function ($value) {
            return $value . 't';
        });

        $catchCB = new InspectableCallback(function ($reason) {
            return $reason . 'c';
        });

        $finallyCB = new InspectableCallback(function () {
            return 'f';
        });

        $promise
            ->then($thenCB)
            ->catch($catchCB)
            ->finally($finallyCB);

        self::assertEquals(1, $thenCB->getTotalCalls());
        self::assertEquals(0, $catchCB->getTotalCalls());
        self::assertEquals(1, $finallyCB->getTotalCalls());

        self::assertSame(['a'], $thenCB->getLastArgs());
        self::assertEquals('at', $thenCB->getLastReturn());

        self::assertEmpty($finallyCB->getLastArgs());
        self::assertEquals('f', $finallyCB->getLastReturn());

        self::assertGreaterThan($thenCB->getLastCallTimestamp(), $finallyCB->getLastCallTimestamp());
    }

}
