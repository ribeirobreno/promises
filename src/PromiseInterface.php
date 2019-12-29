<?php


namespace RibeiroBreno\Promises;


interface PromiseInterface
{

    /**
     * PromiseInterface constructor.
     * @param callable $executor
     */
    public function __construct(callable $executor);

    /**
     * @param PromiseInterface[] $promises
     * @return PromiseInterface
     */
    public static function all(array $promises): PromiseInterface;

    /**
     * @param PromiseInterface[] $promises
     * @return PromiseInterface
     */
    public static function allSettled(array $promises): PromiseInterface;

    /**
     * @param PromiseInterface[] $promises
     * @return PromiseInterface
     */
    public static function race(array $promises): PromiseInterface;

    /**
     * @param mixed $reason
     * @return PromiseInterface
     */
    public static function reject($reason): PromiseInterface;

    /**
     * @param mixed $value
     * @return PromiseInterface
     */
    public static function resolve($value): PromiseInterface;

    /**
     * @param callable $callback
     * @return PromiseInterface
     */
    public function catch(callable $callback): PromiseInterface;

    /**
     * @param callable|null $onFulfilled
     * @param callable|null $onRejected
     * @return PromiseInterface
     */
    public function then(callable $onFulfilled = null, callable $onRejected = null): PromiseInterface;

    /**
     * @param callable $callback
     * @return PromiseInterface
     */
    public function finally(callable $callback): PromiseInterface;

}
