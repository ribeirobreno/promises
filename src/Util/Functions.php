<?php


namespace RibeiroBreno\Promises\Util;


abstract class Functions
{

    /**
     * @param callable|null $fn
     * @param mixed $arg
     * @return mixed
     */
    public static function callOrIdentity(?callable $fn, $arg)
    {
        return call_user_func(self::toClosure($fn), $arg);
    }

    /**
     * @param mixed $fn
     * @return \Closure
     */
    public static function toClosure($fn)
    {
        if (is_callable($fn)) {
            $fn = \Closure::fromCallable($fn);
        } else if (is_null($fn)) {
            $fn = function (...$args) {
                return (count($args) > 1) ? $args : array_shift($args);
            };
        } else {
            $fn = function () use ($fn) {
                return $fn;
            };
        }

        return $fn;
    }
}
